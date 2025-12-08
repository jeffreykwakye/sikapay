<?php
/**
 * @var string $title
 * @var callable $h
 */

$this->title = $title ?? 'How to Manage Your Subscription';

if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Subscription Management</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/subscription">My Subscription</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">How to Pay</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">How to Renew, Upgrade, or Downgrade</h4>
            </div>
            <div class="card-body">
                <p>We are currently finalizing our automated payment gateway. In the meantime, you can manage your subscription by following the manual process below.</p>

                <h5 class="mt-4">Step 1: Choose Your Plan & Contact Support</h5>
                <p>To initiate a change to your subscription, please contact our support team through one of the following channels:</p>
                <ul>
                    <li><strong>Support Page:</strong> <a href="/support">Create a Support Ticket</a></li>
                    <li><strong>Email:</strong> <a href="mailto:support@sikapay.nexusonegh.com">support@sikapay.nexusonegh.com</a></li>
                    <li><strong>Phone:</strong> <a href="tel:+233507585193">+233 50 758 5193</a></li>
                </ul>
                <p>Our team will confirm your desired plan and the total amount due.</p>

                <h5 class="mt-4">Step 2: Make Payment</h5>
                <p>Please use one of the following manual payment methods. After payment, kindly send a confirmation (screenshot or receipt) to our support email to ensure prompt activation.</p>

                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-university"></i> Bank Transfer</h6>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Bank Name:</strong> Access Bank</li>
                            <li class="list-group-item"><strong>Account Name:</strong> Jeffrey Opare Kwakye</li>
                            <li class="list-group-item"><strong>Account Number:</strong> 0421624867301</li>
                            <li class="list-group-item"><strong>Branch:</strong> KNUST</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-mobile-alt"></i> Mobile Money (MoMo)</h6>
                         <ul class="list-group">
                            <li class="list-group-item"><strong>Network:</strong> MTN</li>
                            <li class="list-group-item"><strong>Number:</strong> 0559712921</li>
                            <li class="list-group-item"><strong>Merchant Name:</strong> Jeffrey Opare Kwakye</li>
                        </ul>
                    </div>
                </div>

                <h5 class="mt-4">Step 3: Confirmation</h5>
                <p>Once your payment is confirmed, a support team member or an administrator will manually update your subscription status, and you will receive a confirmation email. This process is typically completed within a few hours during business hours.</p>

                <p class="mt-4 text-info">We appreciate your understanding and are working hard to launch a fully automated system soon. Thank you for choosing SikaPay!</p>
            </div>
        </div>
    </div>
</div>
