<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if ($this->session->flashdata('success_message')): ?>
    <div class="alert alert-success">
        <?php echo $this->session->flashdata('success_message'); ?>
    </div>
<?php endif; $this->session->set_flashdata('success','');?>

<?php if ($this->session->flashdata('error_message')): ?>
    <div class="alert alert-danger">
        <?php echo $this->session->flashdata('error_message'); ?>
    </div>
<?php endif; $this->session->set_flashdata('error','');?>

<style type="text/css">
    .badge-success {
        color: #fff;
        background-color: #28a745;
    }
    .sub-price{
        font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 30px;
    color: #32324D;
    vertical-align: top;
    top: 0;
    bottom: unset;
    }
    .card-padding-10{
        padding: 10px 10px;
    }
    .btn-cancel,.btn-primary {
        /* background-color: #fcdbca !important;
        color: #ce4600; */
        padding: 11px 16px;
    }
    .bold-label {
        font-weight: bold;
    }
    .modal-header .close {
        margin-top: -15px;
    }
    input[type=checkbox], input[type=radio]{
        margin-left: 0px;
    }
	
	.swal-custom-popup {
	  font-size: 16px !important;
	}

	.swal-custom-title {
	  font-size: 20px !important;
	  font-weight: bold;
	}

	.swal-custom-button {
	  background-color: #3085d6;
	  color: white;
	  padding: 8px 16px;
	  font-size: 14px;
	
</style>
<!-- page head start-->
<div class="page-head">
    <h3><?= $page_title ?></h3>
</div>
<!-- page head end-->
<?php 
  // echo "<pre>"; 
  // print_r($invoiceList); 
  // die; 
?>
<!--body wrapper start-->
<div class="wrapper common-add-edit font16-label-p">
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-info">
                <div class="panel-heading panel-heading-main">
                    <h3 class="panel-title panel-title-main">Subscription Plan</h3>
                </div>
                <div class="panel-body">
                    <div class="card col-md-12">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-6">
                                    <h1 style="color: #3e7c33;font-weight: 700;"><?= html_escape(ucfirst($invoicedata->plan_title ?: '')); ?></h1>
                                </div>
                                <div class="col-sm-6 text-right">
                                    <h1 style="color: #3e7c33;font-weight: 700;">
                                        <sub class="sub-price"><?= strtoupper($invoicedata->plan_amount_currency ?: ''); ?></sub> 
                                        <?= $invoicedata->plan_amount ?: ''; ?>
                                    </h1>
                                    <h6 style="margin-top: 10px;"><?= html_escape(ucfirst($invoicedata->plan_description ?: '')); ?></h6>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row" style="background: #e9ecef; padding: 30px;border-radius: 5px; margin-bottom: 10px;">
                                <div class="col-sm-4">
                                    <label class="bold-label">Subscription Start Date</label>
                                    <p>
                                        <?= $startDate = !empty($invoicedata->plan_period_start) 
                                        ? nice_date($invoicedata->plan_period_start, 'd M Y') 
                                        : 'N/A'; 
                                        ?>
                                    </p>
                                </div>
                                <div class="col-sm-4">
                                    <label class="bold-label">Subscription End Date</label>
                                    <p>
                                        <?= $endDate = !empty($invoicedata->plan_period_end) 
                                        ? nice_date($invoicedata->plan_period_end, 'd M Y') 
                                        : 'N/A'; 
                                        ?>
                                    </p>
                                </div>
                                <div class="col-sm-4">
                                    <label class="bold-label">Payment Status</label>
                                    <p style="text-transform: Capitalize;"><?= html_escape($invoicedata->payment_status ?: 'Unknown'); ?></p>
                                </div>
                                <div class="col-sm-4">
                                    <label class="bold-label">Last Payment</label>
                                    <p>
                                        <?php
                                            $payment_methods = lang('payment_methods');

                                            echo $invoicedata->payment_method
                                                ? $payment_methods[$invoicedata->payment_method]
                                                : '-';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-sm-4">
                                    <label class="bold-label">Next Billing Date</label>
                                    <p>
                                        <?= $nextBillingDate = !empty($invoicedata->next_billing_date) 
                                        ? nice_date($invoicedata->next_billing_date, 'd M Y') 
                                        : nice_date($invoicedata->plan_period_end, 'd M Y'); 
                                        ?>
                                    </p>
                                </div>
                                <div class="col-sm-4">
                                    <label class="bold-label">Current Payment Method</label>
                                    <p>
                                        <?php
                                            $payment_methods = lang('payment_methods');

                                            echo $organization->current_payment_method
                                                ? $payment_methods[$organization->current_payment_method]
                                                : '-';
                                        ?>
                                    </p>
                                </div>

                                <div class="col-sm-4">
                                    <label class="bold-label">Subscription Status</label>
                                    <p><?= ucfirst($invoicedata->status) ?></p>
                                </div>

                                <div class="col-sm-4">
                                    <label class="bold-label">Auto Renew Subscription</label>
                                    <p><?=  $invoicedata->auto_renew ? 'Yes' : 'No' ?></p>
                                </div>

                                <div class="col-sm-4">
                                    <label class="bold-label">Promo Code</label>
                                    <p><?php echo $invoicedata->promo_code; ?></p>
                                </div>
                            </div>
                            <?php 
                            $planServices = json_decode($invoicedata->plan_services); ?>        
                            <div class="row">
                                <div class="col-sm-6">
                                    <div style="margin: 10px 10px;">
                                        <?= ($planServices->onboarding_support == 1) ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'; ?>
                                        <b>Onboarding Support</b> 
                                    </div>
                                    <div style="margin: 10px 10px;">
                                        <?= ($planServices->carbon_footprint_report == 1) ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'; ?>
                                        <b>Carbon Footprint Report</b>
                                    </div>
                                    <div style="margin: 10px 10px;">
                                        <?= ($planServices->customizations == 1) ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'; ?>
                                        <b> Customizations</b>
                                    </div>
                                    <div style="margin: 10px 10px;">
                                        <?php if ($planServices->customer_service_support == 1): ?>
                                            <i class="fa fa-phone"></i>
                                        <?php else: ?>
                                            <i class="fa fa-envelope"></i>
                                        <?php endif; ?>
                                        <b>Customer Service Support :</b> <?= ($planServices->customer_service_support == 1) ? 'Call & Email' : 'Email'; ?>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div style="margin: 10px 10px;">
                                        <b> Minimum Years : </b>1 Year 
                                    </div>
                                    <div style="margin: 10px 10px;">
                                        <b>Number of users : </b> <?= 'Up To' . ' - ' . $invoicedata->maximum_no_of_users; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row" style="margin-top: 15px;">
                                <div class="col-lg-6">
                                    <?php if ($this->session->userdata('plan_status') == 'expired_grace_period') { ?>
                                    <a href="<?= base_url("change-subscription"); ?>" class="btn btn-large btn-primary" style="width: 100%;">Renew Plan</a>
									<?php } else if ($invoicedata->status == 'incomplete' || $subscription_error == 'error') { ?>
                                    <button class="btn btn-large btn-primary" style="width: 100%;" disabled>Change Plan</button>
									<?php } else if (empty($upcomingPlan)) { ?>
                                    <a href="<?= base_url("change-subscription"); ?>" class="btn btn-large btn-primary" style="width: 100%;">Change Plan</a>
                                    <?php }else{ ?>
                                    <a href="#" onclick="return false;" class="btn btn-cancel btn-large" style="width: 100%;">Plan Update Requested</a>
                                    <?php } ?>
                                </div>
                                <div class="col-lg-6 text-right">
                                    <?php if ($invoicedata->auto_renew) { ?>
                                        <button class="btn btn-large btn-cancel btn-cancel-subscription" style="width: 100%;">Cancel Auto Renew</button>
                                    <?php } else { ?>
                                        <button class="btn btn-large btn-cancel btn-reactivate-subscription" style="width: 100%;">Re-Activate Auto Renew</button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Method Section -->
        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading panel-heading-main">
                    <h3 class="panel-title panel-title-main">Payment Method</h3>
                </div>
                <div class="panel-body">
                    <form>
                        <div class="card">
                            <div class="card-body">
                                <?= $paymentMethods ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add New Card Modal -->
        <div class="modal fade" id="addCardModal" tabindex="-1" role="dialog" aria-labelledby="addCardModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header modal-header-history">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                        </button>
                        <h3 class="modal-title" id="addCardModalLabel">Add New Card</h3>
                    </div>
                    <div class="modal-body modal-body-history" id="stripe_content">
                        <form id="addPaymentMethodForm" class="">
                            <div class="form-group col-md-12">
                                <label for="cardNumber">Card Number*</label>
                                <div id="card_number" class="field"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="expiry">Expiry*</label>
                                    <div id="card_expiry" class="field"></div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="cvv">CVV*</label>
                                    <div id="card_cvc" class="field"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="button" class="btn btn-success btn-submit btn-block" id="btnAddPaymentMethod">Save</button>
                            </div>
                            <p class="text-center font12 mt-3" style="margin-top: 15px;">
                                By clicking submit you agree to CorpCarbon <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoide Section -->
        <div class="col-md-12">  
            <div class="panel panel-info">
                <div class="panel-heading panel-heading-main">
                    <h3 class="panel-title panel-title-main">Invoices</h3>
                </div>
                <div class="panel-body panel-aligned">
                    <div class="form-group ">
                        <div class="card ">
                            <table class="table table-hover table-condensed" id="print_tab_logs_entry" data-page-length='25'>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Invoice Number</th>
                                        <th>Payment Date</th>
                                        <th>Payment Method</th>
                                        <th>Payment Status</th>
                                        <th>Plan</th>
                                        <th>Purchased Plan Price</th>
                                        <th>Promo Discount</th>
                                        <th>Subscription Start Date</th>
                                        <th>Subscription End Date</th>
                                        <th>Attachment</th>
                                        <th>Comments</th>
                                        <th>View</th>
										<?php 
											if($this->session->userdata('subscription_status') == 'error') { ?>
												<th>Action</th>
										<?php } ?>
										
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $counter = 0;
                                    foreach($invoiceList->data as $invoice) {
										if($invoice->status != 'void' && $invoice->status != 'draft')
										{
											$counter++;
											// Getting line item details
											$lineItem = $invoice->lines->data[0];
											$plan = $lineItem->price->nickname;
											$planPrice = $invoice->amount_paid;
											$couponAmount = $lineItem->discount_amounts[0]->amount ?? 0;
											$subscription_data = $invoice->subscription_data;
											?>

											<tr>
												<td><?php echo $counter; ?></td>
												<td><?php echo $invoice->number; ?></td>
												<td><?php echo date("d M Y", $invoice->status_transitions->paid_at); ?></td>
												<td>
												<?php
													$payment_methods = lang('payment_methods');

													echo $subscription_data->payment_method
														? $payment_methods[$subscription_data->payment_method]
														: '-';
												?>
												</td>
												<td>
													<?php 
													if($invoice->status == 'open' && $organization->current_payment_method != 'fund_transfer') { ?>
														<a style="color:red">Failed</a>
													<?php } else { ?>
														<?php echo ucfirst($subscription_data->payment_status) ? ucfirst($subscription_data->payment_status) : '<a style="color:red">Processing...</a>' ; ?>
													<?php } ?>
												</td>
												<td><?php echo $subscription_data->plan_title ? $subscription_data->plan_title : '-'; ?></td>
												<td>
													<?php echo $subscription_data->final_amount ? strtoupper($lineItem['currency'] ?: '') . ' ' . number_format($subscription_data->final_amount, 2) : '-'; ?>
												</td>
												<td>
													<?php echo $couponAmount ? strtoupper($lineItem['currency'] ?: '') . ' ' . number_format($couponAmount / 100, 2) : '-'; ?>
												</td>
												<td><?php echo $subscription_data->plan_period_start ? date("d M Y", strtotime($subscription_data->plan_period_start)) : '-'; ?></td>
												<td><?php echo $subscription_data->plan_period_end ? date("d M Y", strtotime($subscription_data->plan_period_end)) : '-'; ?></td>
												<td>
													<?php if ($subscription_data->attachment != '') { ?>
													<a class="btn btn-default" onclick="window.location.href='<?php echo site_url('subscription/download-file/' . $subscription_data->attachment); ?>'">
														<i class="fa fa-download" style="font-size:16px"></i> Download
													</a>
													<?php } ?>
												</td>
												<td>
													<?php 
													if($invoice->status == 'open' && $organization->current_payment_method != 'fund_transfer') { ?>
													<a style="color:red">Subscription Processing Error. Kindly retry the payment.</a>
													
													<?php } else if ($invoice->status == 'paid' && empty($subscription_data->payment_method)) { ?>
														
													<a style="color:red">Payment is currently being processed.</a>
													<?php } else { ?>		
															<?php 
															if (trim($subscription_data->attachment_comment) != '') { ?>
																<a class="btn btn-default" data-toggle="popover" title="Comments" data-content="<?= $subscription_data->attachment_comment ?>">
																<i class="fa fa-comment" aria-hidden="true"></i>
															</a>
															<?php } ?>
													<?php } ?>

												</td>
												<td>
													<a class="btn btn-default" target="_blank" href="<?php echo $invoice->hosted_invoice_url; ?>"><i class="fa fa-eye" style="font-size:16px"></i></a>
												</td>
												<?php 
													if($invoice->status == 'open' && $organization->current_payment_method != 'fund_transfer')  { ?>
														<td>
															<a class="btn btn-default" target="_blank" href="<?php echo $invoice->hosted_invoice_url; ?>">Retry Payment</a>
														</td>
												<?php } else if($invoice->status == 'paid' && empty($subscription_data->stripe_invoice_id)){ ?>
													<td>
													<button type="button" class="btn btn-default" id="btnRetrieveData">Update Data</button>
													</td>
												<?php } else { ?>
														<td>
														</td>
												<?php } ?>
											</tr>
											
                                    <?php } } ?>
                                </tbody>
                            </table>            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $this->load->view($this->theme . 'settings/subscription_audit_logs'); ?>

    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<!--body wrapper end-->

<script>
    function openCity(evt, cityName) 
    {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(cityName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    document.getElementById("defaultOpenSubscription").click();

    $(document).ready(function() {
        var print_tab_logs_entry = $('#print_tab_logs_entry').DataTable({
            dom: 'Bfrtip',
            "bPaginate": true,
            "ordering": true,
            "searching": true,
            "pagingType": "input",
            "PaginationType": "bootstrap",
            responsive: true,
            buttons: [],
            "columnDefs": [{
                "targets": -1, // Target the last column
                "orderable": false // Disable sorting
            }]
        });
    });
</script>

<script>
    $(document).ready(function() {

        $('[data-toggle="popover"]').popover({
            html: true,
            trigger: 'click',
            placement: 'top'
        });

        <?php if ($this->session->flashdata('payment_method_added') == 'success') { ?>
            Swal.fire({
                title: "Payment Method Added",
                text: "Payment Method added successfully",
                icon: "success"
            });
        <?php } ?>
		
        <?php if ($this->session->flashdata('payment_method_added') == 'error') { ?>
            Swal.fire({
                title: "Error in Adding Payment Method",
                text: "<?= $this->session->flashdata('payment_method_error_message') ?>",
                icon: "error"
            });
        <?php } ?>

        <?php $this->session->set_flashdata('payment_method_added', ''); ?>


        <?php if ($this->session->flashdata('cancel_subscription') == 'success') { ?>
            Swal.fire({
                title: "Subscription Cancelled",
                text: "Your subscription has been cancelled successfully",
                icon: "success"
            });
        <?php } ?>

        <?php if ($this->session->flashdata('cancel_subscription') == 'error') { ?>
            Swal.fire({
                title: "Error in subscription cancellation",
                text: "<?= $this->session->flashdata('cancel_subscription_message') ?>",
                icon: "error"
            });
        <?php } ?>

        <?php $this->session->set_flashdata('cancel_subscription', ''); ?>
        <?php $this->session->set_flashdata('cancel_subscription_message', ''); ?>

        <?php if ($this->session->flashdata('reactivate_subscription') == 'success') { ?>
            Swal.fire({
                title: "Subscription Re-Activated",
                text: "Your subscription has been activated successfully",
                icon: "success"
            });
        <?php } ?>

        <?php if ($this->session->flashdata('reactivate_subscription') == 'error') { ?>
            Swal.fire({
                title: "Error in subscription re-activation",
                text: "<?= $this->session->flashdata('reactivate_subscription_message') ?>",
                icon: "error"
            });
        <?php } ?>
		
		<?php if ($this->session->userdata('card_error') == 'cardexpired') { ?>
			Swal.fire({
				icon: 'error',
				title: '<strong>Error while processing subscription.</strong>',
				html: '<p style="font-size: 16px;color:#333">' + <?php echo json_encode($this->session->userdata('card_error_message')); ?> + "</p>",
				showConfirmButton: true, // Optional: This will hide the confirm button
				confirmButtonText: 'OK',
				allowOutsideClick: false,  // Prevent user from clicking outside the modal
				allowEscapeKey: false,     // Prevent user from closing with the Escape key
				backdrop: true,
				width : '450px'
			});

		<?php }  $this->session->set_userdata('card_error', ''); ?>
		
		<?php if ($this->session->userdata('subscription_error') == 'error') { ?>
			Swal.fire({
				icon: 'error',
				title: '<strong>Error while processing subscription 1.</strong>',
				html: '<p style="font-size: 16px;color:#333">' + <?php echo json_encode($this->session->userdata('subscription_error_message')); ?> + "</p>",
				showConfirmButton: true, // Optional: This will hide the confirm button
				confirmButtonText: 'OK',
				allowOutsideClick: false,  // Prevent user from clicking outside the modal
				allowEscapeKey: false,     // Prevent user from closing with the Escape key
				backdrop: true,
				width : '450px'
			});

		<?php }  $this->session->set_userdata('subscription_error', ''); ?>
		
		<?php if ($this->session->userdata('update_error') == 'error') { ?>
			Swal.fire({
				icon: 'error',
				title: '<strong>Error while processing subscription.</strong>',
				html: '<p style="font-size: 16px;color:#333">' + <?php echo json_encode($this->session->userdata('update_error_message')); ?> + "</p>",
				showConfirmButton: true, // Optional: This will hide the confirm button
				confirmButtonText: 'OK',
				allowOutsideClick: false,  // Prevent user from clicking outside the modal
				allowEscapeKey: false,     // Prevent user from closing with the Escape key
				backdrop: true,
				width : '450px'
			});

		<?php }  $this->session->set_userdata('update_error', ''); ?>
		
		<?php if ($this->session->userdata('retrieve_data') == 'error') { ?>
			Swal.fire({
				icon: 'error',
				title: '<strong>Error while processing subscription.</strong>',
				html: '<p style="font-size: 16px;color:#333">' + <?php echo json_encode($this->session->userdata('retrieve_data_error_message')); ?> + "</p>",
				showConfirmButton: true, // Optional: This will hide the confirm button
				confirmButtonText: 'OK',
				allowOutsideClick: false,  // Prevent user from clicking outside the modal
				allowEscapeKey: false,     // Prevent user from closing with the Escape key
				backdrop: true,
				width : '450px'
			});

		<?php }  $this->session->set_userdata('update_error', ''); ?>

        <?php $this->session->set_flashdata('reactivate_subscription', ''); ?>
        <?php $this->session->set_flashdata('reactivate_subscription_message', ''); ?>

        var stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');

        var elements = stripe.elements();

        var style = {
            base: {
                // fontWeight: 400,
                // fontFamily: 'Inter',
                // fontSize: '16px',
                // lineHeight: '1.4',
                // color: '#555',
                backgroundColor: '#fff',
                '::placeholder': {
                // color: '#888',
                },
                height: '45px',
            },
            invalid: {
                color: '#eb1c26',
            }
        };

        var cardElement = elements.create('cardNumber', {
            style: style
        });

        cardElement.mount('#card_number');

        var exp = elements.create('cardExpiry', {
            'style': style
        });
        exp.mount('#card_expiry');

        var cvc = elements.create('cardCvc', {
            'style': style
        });

        cvc.mount('#card_cvc');

        $(document).on("click", "#btnAddPaymentMethod", async function() {
            event.preventDefault();

            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });

            if (error) 
            {
                console.error(error.message);
                alert(error.message);
            }
            else
            {
                $.ajax({
                    url: '<?= base_url() ?>subscription/createPaymentMethod',
                    async: false,
                    type: 'POST',
                    data: {
                        paymentMethodId: paymentMethod.id
                    },
                    success: function(response) {
                        window.location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error occurred: ' + error);
                        window.location.reload();
                    }
                });
            }
        });

		$(document).on("click", "#btnRetrieveData", async function() {
            event.preventDefault();

            $.ajax({
                    url: '<?= base_url() ?>subscription/retrieveData',
                    async: false,
                    type: 'POST',
                    success: function(response) {
                        window.location.reload();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error occurred: ' + error);
                        window.location.reload();
                    }
                });
        });

        $(document).on('click', '.btn-cancel-subscription', function() {
            Swal.fire({
                title: "Do you want to cancel subscription?",
                icon: "warning",
                showDenyButton: true,
                showCancelButton: false,
                confirmButtonText: "Yes",
                denyButtonText: "No"
            }).then((result) => {
                if (result.isConfirmed) 
                {
                    $.ajax({
                        url: '<?= base_url() ?>subscription/cancelSubscription',
                        async: false,
                        success: function(response) {
                            window.location.reload();
                        },
                        error: function(xhr, status, error) {
                            window.location.reload();
                        }
                    });
                }
            });
        });

        $(document).on('click', '.btn-reactivate-subscription', function() {
            Swal.fire({
                title: "Do you want to Re-Activate subscription?",
                icon: "warning",
                showDenyButton: true,
                showCancelButton: false,
                confirmButtonText: "Yes",
                denyButtonText: "No"
            }).then((result) => {
                if (result.isConfirmed) 
                {
                    $.ajax({
                        url: '<?= base_url() ?>subscription/reactivateSubscription',
                        async: false,
                        success: function(response) {
                            window.location.reload();
                        },
                        error: function(xhr, status, error) {
                            window.location.reload();
                        }
                    });
                }
            });
        });
    });
</script>