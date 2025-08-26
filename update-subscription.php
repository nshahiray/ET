<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $assets = base_url() . 'themes/site/assets/'; ?>
<style>
	.plan-leble{
		font-size: 16px;line-height:24px;font-weight: 600;
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
            <?php if ($this->session->userdata('plan_status') == 'expired_grace_period') { ?>
                <h3>Renew Subscription</h3>
            <?php }else{ ?>
                <h3><?= $page_title ?></h3>
            <?php } ?>
        </div>
         <div class="col-md-1">
            <a href="javascript:history.back()" class="btn btn-primary">Back</a>
        </div>
    </div>
</div>

<div class="wrapper common-add-edit">
	<div class="row">
		<div class="col-lg-12">
			<div class="panel panel-color panel-inverse">
				<div class="panel-body">
					<form method="POST" action="">
						<input type="hidden" name="proration_date" value="<?= $proration_date; ?>">
						<div class="" id="stripe_content">

							<div class="row">
								<div class="col-sm-5">
									<div class="panel panel-info">
										<div class="panel-heading panel-heading-main">
											<h3 class="panel-title panel-title-main">Select Payment Method</h3>
										</div>
										<div class="panel-body">
											<form>
												<div class="card">
													<div class="card-body font16-label-p">
														<?= $paymentMethods ?>
													</div>
												</div>
											</form>
										</div>
									</div>	
								</div>
								<div class="col-sm-7">
									<div class="panel panel-info"><div class="panel-body">
									<div class="row">
										<div class="col-sm-6">
											<div class="plan-leble" id="planNameShow"><?= $selected_plan->plan_title ?></div>
											<div style="font-size: 16px;color: #a1a1a1;" id="planDescriptionShow"><?= $selected_plan->plan_description ?></div>
										</div>
										<div class="col-sm-6">
											<div class="plan-curr-amount text-right" style="font-weight: 700;" id="planAmountShow"><?= strtoupper($selected_plan->code) . ' ' . number_format($selected_plan->amount, 2) ?></div>
										</div>
									</div>

									<div class="row">
										<hr style="border-color: lightgray; width: 100%;">
									</div>
									<br>

									<?php if ($this->session->userdata('plan_status') == 'expired_grace_period') { ?>
										<div class="row">
											<div class="col-sm-6">
												<div class="plan-leble" id="planNameShow">Renew Plan for <?= $proration->new_plan->plan_title ?></div>
												<div style="font-size: 16px;color: #a1a1a1;" id="planDescriptionShow"></div>
											</div>
											<div class="col-sm-6">
												<div class="plan-curr-amount text-right" style="font-weight: 700;" id="planAmountShow"><?= strtoupper($proration->new_plan->code) . ' ' . number_format($selected_plan->amount, 2) ?></div>
											</div>
										</div><br>
									
									<?php }else{ ?>
										<div class="row">
											<div class="col-sm-6">
												<div class="plan-leble" id="planNameShow">Time Left for <?= $proration->new_plan->plan_title ?></div>
												<div style="font-size: 16px;color: #a1a1a1;" id="planDescriptionShow"><?= $proration->new_plan->description ?></div>
											</div>
											<div class="col-sm-6">
												<div class="plan-curr-amount text-right" style="font-weight: 700;" id="planAmountShow"><?= strtoupper($proration->new_plan->code) . ' ' . $proration->new_plan->amount ?></div>
											</div>
										</div><br>

										<div class="row">
											<div class="col-sm-6">
												<div class="plan-leble" id="planNameShow">Unused Plan</div>
												<div style="font-size: 16px;color: #a1a1a1;" id="planDescriptionShow"><?= $proration->current_plan->description ?></div>
											</div>
											<div class="col-sm-6">
												<div class="plan-curr-amount text-right" style="font-weight: 700;" id="planAmountShow">- <?= strtoupper($proration->current_plan->code) . ' ' . abs($proration->current_plan->amount) ?></div>
											</div>
										</div><br>
                                    <?php } ?>

									<div id="invoiceContent">
										<div class="row">
											<hr style="border-color: lightgray; width: 100%;">
										</div>
										<div class="row">
											<div class="col-sm-8">
												<div class="font-weight-bold" style="font-weight: bold;font-size: 16px;line-height: 24px;">Total Due
												</div>
											</div>
											<div class="col-sm-4">
												<div class="text-right">
												    <?php if ($this->session->userdata('plan_status') == 'expired_grace_period') { ?>
												        <span id="discountedPlanPrice" style="font-weight: bold;"><?= strtoupper($selected_plan->code) . ' ' . number_format($selected_plan->amount, 2) ?></span>
												    <?php }else{ ?>
													    <span id="discountedPlanPrice" style="font-weight: bold;"><?= strtoupper($selected_plan->code) . ' ' . $proration->current_due ?></span>
													<?php } ?>
												</div>
											</div>
											<div class="col-sm-12 text-left">
												<a href="<?php echo base_url(); ?>change-subscription" style="font-size: 16px;" class="change-plan-lab">Change Plan</a>
											</div>
										</div>
									</div>
									</div></div>
								</div>

							</div>

							<div class="row">
								<div class="col-sm-3"></div>
								<div class="col-sm-3">
									<?php if(!empty($organization->organization->current_payment_method)) { ?>
										<button type="submit" class="green-btn" id="payBtn"> Pay Now</button>
									<?php } else { ?>
										<button type="button" class="green-btn disabled" id="payBtn" onclick="defaultPaymentAlert()"> Pay Now</button>
									<?php } ?>
								</div>
								<div class="col-sm-3"><a href="<?php echo base_url(); ?>subscription" style="font-size: 16px;margin-top: 30px;padding: 12px 16px;width: 100%;" class="btn btn-large btn-cancel">Cancel</a></div>
								<div class="col-sm-3"></div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script>

	function defaultPaymentAlert()
	{
		Swal.fire({
			title: "Select Default Payment Method",
			text: "Please set a default payment method before proceeding",
			icon: "error"
		});
	}
	


</script>