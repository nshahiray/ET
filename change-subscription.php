<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $assets = base_url() . 'themes/site/assets/'; ?>
<style type="text/css">

    .best-value {
        background-color: #CEF1B8;
        color: #2A6A25;
        margin: 0px 5px;
        padding: 4px;
        border-radius: 2px;
        font-family: Inter;
        font-size: 11px;
        font-style: normal;
        font-weight: 700;
        line-height: 16px;
        text-transform: capitalize;
        position: absolute;
        right: 30px;
        top: 20px;
    }
    .package-sec{
        background: #fff;
        padding: 24px;
        box-shadow: 0px 1px 4px 0px rgba(33, 33, 52, 0.10);
        border-radius: 4px;
        margin: 30px auto 80px;
    }
    .packages-design-sec .title{
        font-family: 'Inter', sans-serif;
        font-size: 32px;
        font-style: normal;
        font-weight: 700;
        line-height: 40px;
        color: #32324D;
        margin-bottom: 0;
    }
    .package-column{
        border-radius: 4px;
        border: 1px solid #EAEAEF;
        padding: 20px;
        background: #FFF;
        margin-top: 15px;
    }
    .package-column .list-group-item{
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        font-style: normal;
        font-weight: 400;
        line-height: 18px;
        padding: 10px 15px;
        color: #32324D;
        border-radius: 0px;
        border-top: 1px solid #EAEAEF !important;
        border: 0;
        display: flex;
        justify-content: space-between;
    }
    .package-upper .card-title a {
        font-family: 'Inter', sans-serif;
        font-size: 80px;
        font-style: normal;
        font-weight: 400;
        line-height: 80px;
        color: #3E7C33;
        display: flex;
        justify-content: center;
    }

    .package-upper .card-title a span {
        font-family: 'Inter', sans-serif;
        font-size: 24px;
        font-style: normal;
        font-weight: 400;
        line-height: 40px;
        color: #32324D;
        margin-top: 5px;
    }

    .package-upper p{
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        font-style: normal;
        font-weight: 400;
        line-height: 24px;
        color: #666687;
    }

    .text-center {
        text-align: center;
    }
    .card-header {
        font-family: 'Inter', sans-serif;
        font-size: 32px;
        font-style: normal;
        font-weight: 700;
        line-height: 40px;
        color: #3E7C33;
        margin-top: 30px;
    }
    .login-logo-img {
        margin-bottom: 5px; 
    }
</style>

<?php if ($this->session->flashdata('success_message')): ?>
    <div class="alert alert-success">
        <?php echo $this->session->flashdata('success_message'); ?>
        <?php $this->session->unset_userdata('success_message'); ?>
    </div>
<?php endif; ?>

<?php if ($this->session->flashdata('error_message')): ?>
    <div class="alert alert-danger">
        <?php echo $this->session->flashdata('error_message'); ?>
        <?php $this->session->unset_userdata('error_message');?>
    </div>
<?php endif; ?>

<div class="page-head">
    <div class="row"> 
        <div class="col-md-11">
            <h3><?= $page_title ?></h3>
        </div>
        <?php $plan_status = $this->session->userdata('plan_status'); 
			if($plan_status !== 'expired_grace_period')
			{?>		
				<div class="col-md-1">
					<a href="javascript:history.back()" class="btn btn-primary">Back</a>
				</div>
		<?php }?>
    </div>
</div>

<div class="wrapper common-add-edit">
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-color panel-inverse">
                <div class="panel-heading panel-heading-main">
                    <h3 class="panel-title panel-title-main"><?= $page_title ?></h3>
                </div>
                <div id="all_plans">
                    <form id="paymentFrm" class="form" method="POST">
                        <?php  

                        if (isset($active_subscription)) 
                        {
                            $current_date = date("Y-m-d H:i:s");
                            $subscription_expired = strtotime($current_date) >= strtotime($active_subscription->plan_period_end);
                        }
                        ?>

                        <?php $current_plan_amount = $current_plan ? $current_plan->amount : 0; ?>

                        <?php foreach ($active_plans as $plan) { ?>
                            <?php if ($plan->amount >= $current_plan_amount) { ?>
                                <div class="col-sm-4">
                                    <div class="col-sm-12">
                                        <div class="text-right">
                                            <?php
                                            $class = ($plan->best_value == 1) ? 'best-value' : '';

                                            echo '<span class="' . $class . '">' . ($plan->best_value == 1 ? 'Best Value' : '') . '</span>';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="package-column">
                                            <div class="card">
                                                <div>
                                                    <?php if ($plan->plan_id === $old_stripe_plan_id) { ?>
                                                        <span class="badge bg-success pos-abs-set text-dark">Current Plan</span>
                                                    <?php } ?>
                                                </div>
                                                <div class="card-header text-center">
                                                    <b><?php echo ucfirst($plan->plan_title); ?></b>
                                                </div>
                                                <div class="card-body">
                                                    <div class="package-upper">
                                                        <h5 class="card-title text-center">
                                                            <a href="#" onclick="return false;">
                                                                <span><?php echo strtoupper($plan->code); ?></span>
                                                                <?php echo $plan->amount; ?>
                                                            </a>
                                                        </h5>

                                                        <p class="card-text text-center">
                                                            <?php echo $plan->plan_description; ?>
                                                        </p>

                                                        <?php 
                                                            if($current_plan->currency->code == $plan->code) { ?>
                                                                <a href="subscription/update-subscription/<?= $plan->id ?>" class="btn btn-primary select_plan" data-plan_id="<?php echo $plan->plan_id; ?>" style="width: 100%; margin:10px 0px;" <?php echo ($plan->plan_id == $old_stripe_plan_id) ? "disabled" : ""; ?>>
                                                                    Select Plan
                                                                </a>
                                                            <?php } else { ?>
                                                                <a  href="" class="btn btn-default" data-plan_id="<?php echo $plan->plan_id; ?>" style="width: 100%; margin:10px 0px; background-color: silver;" disabled>
                                                                Select Plan
                                                                </a>
                                                        <?php } ?>
                                                    </div>

                                                    <ul class="list-group list-group-flush" style="margin-bottom: 0;">
                                                        <li class="list-group-item">
                                                            <?php echo ($plan->onboarding_support == 1) ? "<span>Onboarding Support</span>" : "<span class='no-customizations'>Onboarding Support</span>";
                                                            ?>

                                                            <span>
                                                                <b>
                                                                    <?php echo ($plan->onboarding_support == 1) ? "<img class='login-logo-img' src='" . $assets . "img/check-icon.svg' />" : " ";
                                                                    ?>

                                                                </b>
                                                            </span>
                                                        </li>
                                                        <li class="list-group-item">
                                                            <?php echo ($plan->carbon_footprint_report == 1) ? "<span>Carbon Footprint Report</span>" : "<span class='no-customizations'>Carbon Footprint Report</span>";
                                                            ?>
                                                            <span>
                                                                <b>
                                                                    <?php echo ($plan->carbon_footprint_report == 1) ? "<img class='login-logo-img' src='" . $assets . "img/check-icon.svg' />" : " "; 
                                                                    ?>
                                                                </b>
                                                            </span>
                                                        </li>
                                                        <li class="list-group-item">
                                                            <?php echo ($plan->customizations == 1) ? "<span>Customizations</span>" : "<span class='no-customizations'>Customizations</span>";
                                                            ?>
                                                            <span>
                                                                <b>
                                                                    <?php echo ($plan->customizations == 1) ? "<img class='login-logo-img' src='" . $assets . "img/check-icon.svg' />" : ''; 
                                                                    ?>
                                                                </b>
                                                            </span>
                                                        </li>
                                                        <li class="list-group-item">
                                                            <span>Customer Service Support</span>
                                                            <span>
                                                                <b>
                                                                    <?php echo ($plan->customer_service_support == 1) ? "<span>Email & Phone</span>" : "<span>Email Only</span>";
                                                                    ?>
                                                                </b>
                                                            </span>
                                                        </li>
                                                        <li class="list-group-item">
                                                            <?php
                                                            echo ($plan->aI_tool == 1) ? "<span>AI Tool</span>" : "<span class='no-customizations'>AI Tool</span>";
                                                            ?>
                                                            <span>
                                                                <b>
                                                                    <?php echo (($plan->aI_tool == 1) ? "<img class='login-logo-img' src='" . $assets . "img/check-icon.svg' />" : " "); ?>
                                                                </b>
                                                            </span>
                                                        </li>
                                                        <li class="list-group-item">
                                                            <span>Contract Term</span>
                                                            <span><b><?php echo ($plan->number_of_year * 12); ?> Months</b></span>
                                                        </li>
                                                        <li class="list-group-item">
                                                            <span>Maximum Number of Users</span>
                                                            <span>
                                                                <b><?php echo $plan->maximum_no_of_users; ?></b>
                                                            </span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                        <input type="hidden" name="stripe_customer_id" id="stripe_customer_id" value="<?php echo $stripe_customer_id; ?>">
                        <input type="hidden" name="old_subscription_id" id="old_subscription_id" value="<?php echo $old_subscription_id; ?>">
                        <input type="hidden" name="old_stripe_plan_id" id="old_stripe_plan_id" value="<?php echo $old_stripe_plan_id; ?>">
                        <input type="hidden" name="new_stripe_plan_id" id="new_stripe_plan_id" value="">

                        <div class="panel-body" id="stripe_content" style="display: none;">
                            <div id="paymentResponse"></div>
                            <div class="form-group">
                                <label>CARD NUMBER</label>
                                <div id="card_number" class="field"></div>
                            </div>
                            <div class="row">
                                <div class="left">
                                    <div class="form-group">
                                        <label>EXPIRY DATE</label>
                                        <div id="card_expiry" class="field"></div>
                                    </div>
                                </div>
                                <div class="right">
                                    <div class="form-group">
                                        <label>CVC CODE</label>
                                        <div id="card_cvc" class="field"></div>
                                    </div>
                                </div>
                            </div>
                            <span class="btn btn-success change-button" id="change-button" >Change now</span>
                        </div>
                    </form> 
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(".select_plan").click(function () {
        var submit_plan = $(this).data("plan_id");
        $("#new_stripe_plan_id").val(submit_plan);
        
        if(confirm("Are you sure you want to change the plan ?"))
        {
            var form1 = document.getElementById('paymentFrm');
            form1.submit();
        } 
        else 
        {
            alert("You select No");
            return false;
        }

        form1.submit();
    });
</script>

<script>
    $(document).ready(function() {

        $(document).on('click', '.removePaymentMethod', function() {

            Swal.fire({
                title: "Do you want to Upgrade Your plan ?",
                showDenyButton: true,
                showCancelButton: false,
                confirmButtonText: "Yes",
                denyButtonText: "No"
            }).then((result) => {
                if (result.isConfirmed) 
                {
                    var paymentMethodId = $(this).data('id');

                    $.ajax({
                        url: '<?= base_url() ?>subscription/upgradeYourplan',
                        async: false,
                        type: 'POST',
                        data: {
                            paymentMethodId: paymentMethodId
                        },
                        success: function(response) {

                            $("#card_" + paymentMethodId).fadeOut(400, function() {
                                $(this).remove();
                            });

                            Swal.fire({
                                title: "Payment Method Removed",
                                text: "Payment Method removed successfully",
                                icon: "success"
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error in removing payment method: ' + error);

                            Swal.fire({
                                title: "Error in removing payment method",
                                text: "There was an error removing payment method.",
                                icon: "error"
                            });
                        }
                    });
                }
            });
        });

        $('#sendReminderBtn').click(function() {
            $.ajax({
                    url: '<?php echo site_url("send-plan-expiry-reminder"); ?>', // Adjust the path as needed
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            $('#statusMessage').text('Plan expiry reminders sent successfully.');
                        } else {
                            $('#statusMessage').text('Failed to send reminders.');
                        }
                    },
                    error: function() {
                        $('#statusMessage').text('An error occurred while sending reminders.');
                    }
                });
        });
    });
</script>