<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\Validator;
use Jeffrey\Sikapay\Models\SupportMessageModel;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Core\Auth;

class SupportController extends Controller
{
    protected SupportMessageModel $supportMessageModel;

    public function __construct()
    {
        parent::__construct();
        $this->supportMessageModel = new SupportMessageModel();
    }

    /**
     * Displays the support page.
     * For Tenant Admins, it shows a form to create a ticket and their history.
     * For Super Admins, it shows a list of all tickets from all tenants.
     */
    public function index(): void
    {
        try {
            if (Auth::isSuperAdmin()) {
                // Super Admin: View all tickets
                $messages = $this->supportMessageModel->getAllMessages();
                $this->view('support/super_admin_index', [
                    'title' => 'Manage Support Tickets',
                    'messages' => $messages,
                ]);
            } else {
                // Tenant Admin: View their own tickets and create new ones
                $this->checkPermission('tenant:send_support_message');
                $messages = $this->supportMessageModel->getMessagesByTenant($this->tenantId);
                $this->view('support/index', [
                    'title' => 'Support Center',
                    'messages' => $messages,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error("Failed to load support page for Tenant {$this->tenantId}: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the support page due to a system error.");
        }
    }

    /**
     * Store a new support message from the tenant.
     */
    public function store(): void
    {
        // Security: Super Admins should not be able to create tickets
        if (Auth::isSuperAdmin()) {
            ErrorResponder::respond(403, "This action is not available for your role.");
            return;
        }

        $this->checkPermission('tenant:send_support_message');

        $validator = new Validator($_POST);
        
        $messageId = $validator->get('message_id', 'int', null);
        $isReply = ($messageId !== null);

        if ($isReply) {
            // Validate for replies
            $validator->validate([
                'message_id' => 'required|int',
                'reply_content' => 'required|min:5',
            ]);
        } else {
            // Validate for new tickets
            $validator->validate([
                'subject' => 'required|min:5|max:255',
                'message' => 'required|min:20',
            ]);
        }

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Failed to send message: " . implode(', ', $validator->errors());
            $_SESSION['flash_input'] = $validator->all();
            $this->redirect('/support');
            return;
        }

        try {
            if ($isReply) {
                // Check if the ticket exists and is not closed before allowing a reply
                $currentTicket = $this->supportMessageModel->find((int)$messageId);
                if (!$currentTicket || (int)$currentTicket['tenant_id'] !== $this->tenantId) {
                    $_SESSION['flash_error'] = "Ticket not found or unauthorized.";
                    $this->redirect('/support');
                    return;
                }
                if ($currentTicket['status'] === 'closed') {
                    $_SESSION['flash_error'] = "Cannot reply to a closed ticket. Please open a new ticket if your issue persists.";
                    $this->redirect('/support');
                    return;
                }
                
                $replyContent = $validator->get('reply_content');
                $newStatus = ($currentTicket['status'] === 'open' || $currentTicket['status'] === 'reopened') ? $currentTicket['status'] : 'reopened'; // Keep current status if open/reopened, otherwise set to reopened

                $this->supportMessageModel->appendMessage(
                    $messageId,
                    $replyContent,
                    $this->tenantId,
                    $newStatus
                );
                $_SESSION['flash_success'] = "Your reply has been sent successfully.";

                // Notification to Super Admin about tenant reply
                $superAdminUsers = $this->userModel->getSuperAdminUsers(); 
                foreach ($superAdminUsers as $saUser) {
                    $this->notificationService->notifyUser(
                        1, // Super Admin Tenant ID
                        (int)$saUser['id'], // Super Admin User ID
                        'SUPPORT_TICKET_REPLY',
                        'Tenant Replied to Support Ticket',
                        "Tenant {$this->tenantName} has replied to ticket #{$messageId} ('{$currentTicket['subject']}'). Status: {$newStatus}."
                    );
                }

            } else {
                // Handle new ticket creation
                $newTicketId = $this->supportMessageModel->create([ // Capture new ID
                    'tenant_id' => $this->tenantId,
                    'user_id' => Auth::userId(),
                    'subject' => $validator->get('subject'),
                    'message' => $validator->get('message'),
                ]);
                $_SESSION['flash_success'] = "Your support message has been sent successfully. We will get back to you shortly.";

                // Notifications for new ticket
                // 1. To Super Admin
                $superAdminUsers = $this->userModel->getSuperAdminUsers(); 
                foreach ($superAdminUsers as $saUser) {
                    $this->notificationService->notifyUser(
                        1, // Super Admin Tenant ID
                        (int)$saUser['id'], // Super Admin User ID
                        'NEW_SUPPORT_TICKET',
                        'New Support Ticket Received',
                        "A new support ticket (#{$newTicketId}) has been submitted by Tenant {$this->tenantName}: '{$validator->get('subject')}'."
                    );
                }
                // 2. To Tenant (sender)
                $this->notificationService->notifyUser(
                    $this->tenantId, // Current Tenant ID
                    Auth::userId(), // Sender User ID
                    'SUPPORT_TICKET_CONFIRMATION',
                    'Support Ticket Submitted Successfully',
                    "Your support ticket ('{$validator->get('subject')}') has been successfully submitted. We will get back to you shortly."
                );
            }

            $this->redirect('/support');

        } catch (\Throwable $e) {
            Log::error("Failed to store support message for Tenant {$this->tenantId}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A critical error occurred while sending your message. Please try again.";
            $this->redirect('/support');
        }
    }

    /**
     * Handles the Super Admin's response to a support ticket.
     */
    public function respond(string $id): void
    {
        $id = (int)$id;
        // Security: Only Super Admins can respond
        if (!Auth::isSuperAdmin()) {
            ErrorResponder::respond(403, "You do not have permission to perform this action.");
            return;
        }

        // Check if the ticket is closed before allowing a response
        $ticketToRespond = $this->supportMessageModel->find($id);
        if (!$ticketToRespond) {
            $_SESSION['flash_error'] = "Ticket not found.";
            $this->redirect('/support');
            return;
        }
        if ($ticketToRespond['status'] === 'closed') {
            $_SESSION['flash_error'] = "Cannot respond to a closed ticket. Please reopen it first if needed.";
            $this->redirect('/support');
            return;
        }

        $validator = new Validator($_POST);
        $validator->validate([
            'super_admin_response' => 'required|min:10',
            'status' => 'required|in:open,in_progress,closed,reopened',
        ]);

        if ($validator->fails()) {
            $_SESSION['flash_error'] = "Failed to send response: " . implode(', ', $validator->errors());
            $this->redirect('/support');
            return;
        }

        try {
            $this->supportMessageModel->updateMessage($id, [
                'super_admin_response' => $validator->get('super_admin_response'),
                'status' => $validator->get('status'),
            ]);

            $_SESSION['flash_success'] = "Response sent and ticket status updated successfully.";
            
            // Notification to Tenant about Super Admin response
            $originalTicket = $this->supportMessageModel->find($id);
            if ($originalTicket) {
                $this->notificationService->notifyUser(
                    (int)$originalTicket['tenant_id'], // Tenant ID from original ticket
                    (int)$originalTicket['user_id'], // Tenant User ID
                    'SUPPORT_TICKET_RESPONSE',
                    'Your Support Ticket Has a Response',
                    "Your support ticket #{$id} ('{$originalTicket['subject']}') has received a response. Status: {$validator->get('status')}."
                );
            }

            $this->redirect('/support');

        } catch (\Throwable $e) {
            Log::error("Failed to respond to support message ID {$id}: " . $e->getMessage());
            $_SESSION['flash_error'] = "A critical error occurred while sending the response. Please try again.";
            $this->redirect('/support');
        }
    }
}
