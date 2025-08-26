<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once('traits/common_db_function.php');

class Subscription
{
	use common_db_functions;

	protected $CI;

	private $stripe;

	private $ogranization_id;

	private $ogranization;

	private $subscription;

	private $plan;

	private $users;

	
	public function __construct($organization_id = null)
	{
		$this->CI =& get_instance();
		$this->CI->load->database();
		$this->CI->load->library('session');
		$this->CI->load->library('stripe');

		$this->organization_id = $organization_id;
		$this->CI->load->model('mdl_site', 'mdl');
		$this->setStripeInstance();
		$this->setOrganizationData();
	}

	protected function setStripeInstance()
	{
		\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $this->stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
	}

	protected function setOrganizationData()
	{
	    $this->organization_id = $this->organization_id ? $this->organization_id : $this->getOrganizationId();

	    $this->organization = $this->CI->db->get_where('sam_organizations', ['id' => $this->organization_id])->row();

	    if (!empty($this->organization)) 
	    {
	        $this->setSubscriptionData();
	        $this->setUsers();
	    }
	}

	protected function setSubscriptionData()
	{
	    if (isset($this->organization->subscription_id)) 
	    {
	        $this->subscription = $this->CI->db->order_by('id', 'DESC')
								->get_where('sam_organization_subscriptions', ['organization_id' => $this->organization->id])
								->row();

	        if (!empty($this->subscription))
	        {
	            $this->setPlanData();
	        }
	    }
	}

	protected function setPlanData()
	{
	    if (isset($this->subscription->plan_id)) 
	    {
	        $this->plan = $this->CI->db->get_where('sam_plans', ['id' => $this->subscription->plan_id])->row();

	        if (isset($this->plan->currency_id))
	        {
	        	$this->plan->currency = $this->CI->db->get_where('sam_currencies', ['id' => $this->plan->currency_id])->row();
	        }
	    }
	}

	public function getOrganizationData()
	{
	    if (!$this->organization)
	    {
	        return null;
	    }

	    $orgData = new stdClass();
	    $orgData->organization = $this->organization;
	    $orgData->users = $this->users;
	    $orgData->subscription = $this->subscription;
	    $orgData->plan = $this->plan;

	    return $orgData;
	}

	public function setUsers()
	{
		$this->users = $this->CI->db->get_where('sam_user', ['organization_id' => $this->organization_id])->first_row();
	}

	public function getStripeSubscription()
	{
	    if (isset($this->stripe)) 
        {
            $stripeSubscription = $this->stripe->subscriptions->retrieve($this->subscription->stripe_subscription_id);

            return $stripeSubscription;
        }

	    return null;
	}

	public function getStripeCustomer()
	{
	    if (!empty($this->organization->stripe_customer_id)) 
	    {
	        try 
	        {
	            if (isset($this->stripe)) 
	            {
	                $stripeCustomer = $this->stripe->customers->retrieve($this->organization->stripe_customer_id);

	                return $stripeCustomer;
	            }
	            else 
	            {
	                log_message('error', 'Stripe library is not initialized.');
	            }
	        } 
	        catch (\Exception $e) 
	        {
	            log_message('error', 'Error retrieving Stripe customer: ' . $e->getMessage());
	        }
	    } 
	    else 
	    {
	        log_message('error', 'Stripe customer ID is not set.');
	    }

	    return null;
	}

	public function getAllPaymentMethods($limit = 10, $show_fund_transfer = true)
	{
	    if (!empty($this->organization->stripe_customer_id)) 
	    {
	        try 
	        {
	            if (isset($this->stripe)) 
	            {
	                $paymentMethods = $this->stripe->customers->allPaymentMethods($this->organization->stripe_customer_id, ['limit' => $limit]);

	                $cards = [];

	                foreach ($paymentMethods as $paymentMethod)
	                {
	                	$cards[$paymentMethod->id] = ucfirst($paymentMethod->card->brand) . '  ****  ' . $paymentMethod->card->last4;
	                }

	                if ($show_fund_transfer)
	                {
	                	$cards['fund_transfer'] = 'CorpCarbon Bank Transfer';
	                }

	                // echo "<pre>"; print_r($cards);die;
	                return $cards;
	            }
	            else 
	            {
	                log_message('error', 'Stripe library is not initialized.');
	            }
	        } 
	        catch (\Exception $e) 
	        {
	            log_message('error', 'Error retrieving Payment Methods: ' . $e->getMessage());
	        }
	    } 
	    else 
	    {
	        log_message('error', 'Stripe customer ID is not set.');
	    }

	    return null;
	}

	public function setDefaultPaymentMethod($paymentMethodId)
	{
		$this->CI->db->trans_start();

		try
		{
			if ($paymentMethodId == 'fund_transfer')
			{
				$this->stripe->customers->update(
		            $this->organization->stripe_customer_id,
		            ['invoice_settings' => ['default_payment_method' => null]]
		        );

				// turn off auto renewal  if fund transfer is selected.

				if (!empty($this->subscription->stripe_subscription_id))
				{
					$this->stripe->subscriptions->update(
					  	$this->subscription->stripe_subscription_id,
					  	['cancel_at_period_end' => true]
					);	
				}
		        

		        $this->updateOrganization([
		        	'where' => ['id = ' . $this->organization->id],
		        	'data' => ['current_payment_method' => 'fund_transfer']
		        ]);

		        $this->updateSubscriptionLog(['default_payment_method' => 'fund_transfer']);
			}
			else
			{
				$this->stripe->customers->update(
		            $this->organization->stripe_customer_id,
		            ['invoice_settings' => ['default_payment_method' => $paymentMethodId]]
		        );

				// turn on auto renewal if stripe selected and auto renewal in our
				// database is set to true
		        if (!empty($this->subscription->stripe_subscription_id) && $this->subscription->auto_renew == 1)
		        {
		        	$subscription = $this->stripe->subscriptions->update(
					  	$this->subscription->stripe_subscription_id,
					  	['cancel_at_period_end' => false]
					);
		        }

		        $this->updateOrganization([
		        	'where' => ['id = ' . $this->organization->id],
		        	'data' => ['current_payment_method' => 'stripe']
		        ]);

		        $this->updateSubscriptionLog(['default_payment_method' => 'stripe']);
			}

			$this->setOrganizationData();

			$this->CI->db->trans_complete();

	        if ($this->CI->db->trans_status() === FALSE) 
	        {
	            throw new Exception('Database transaction failed.');
	        }
		}
		catch(Exception $e)
		{
			$this->CI->db->trans_rollback();

	        throw $e;
		}
		
	}

	public function renderPaymentMethods($is_admin = false, $show_add_button = true, $show_remove_button = true)
	{
		$paymentMethods = $this->getAllPaymentMethods();
		$defaultPaymentMethodId = $this->getDefaultPaymentMethod();

		$str = '';

		foreach ($paymentMethods as $pKey => $paymentMethod)
		{
			$checked = ($pKey == $defaultPaymentMethodId || $this->organization->current_payment_method == 'fund_transfer') ? 'checked' : '';
			$btnClass = ($pKey == $defaultPaymentMethodId || $pKey == 'fund_transfer') ? 'hidden' : '';
			$btnClass .= ($pKey == 'fund_transfer') ? ' fund-transfer' : '';

			$str .= '<div class="form-group" id="card_' . $pKey . '" style="margin-bottom: 8px;">';
			$str .= '<input type="radio" name="default_payment_method" id="default_payment_method_' . $pKey . '"data-id="' . $pKey . '" data-organization_id="' . $this->organization->id . '" ' . $checked . '>';
			$str .= '&nbsp;';
		    $str .= '<label for="default_payment_method_' . $pKey . '">';
		    $str .= $paymentMethod;
		    $str .= '</label>';

		    if ($show_remove_button)
		    {
		    	$str .= '<button type="button" class="btn btn-link remove-payment-method ' . $btnClass . '" id="removecard_' . $pKey . '" data-id="' . $pKey . '" data-organization_id="' . $this->organization->id .'">';
			    $str .= 'Remove';
			    $str .= '</button>';	
		    }

	    	$str .= '</div>';
		}

		if ($show_add_button)
    	{
    		$str .= '<button type="button" class="btn btn-success" data-toggle="modal" data-target="#addCardModal">Add New Card</button>';	
    	}

    	$base_url = base_url();

    	$setDefaultPaymentMethodUrl = $is_admin 
    		? $base_url . 'subscriptions/setDefaultPaymentMethod'
    		: $base_url . 'subscription/setDefaultPaymentMethod';

    	$removePaymentMethodUrl = $is_admin
    		? $base_url . 'subscriptions/removePaymentMethod'
    		: $base_url . 'subscription/removePaymentMethod';

    	$str .= <<<SCRIPT
        <script type="text/javascript">
        	$(document).on('change', 'input[name="default_payment_method"]', function() {

            var paymentMethodId = $(this).data('id');
            var organizationId = $(this).data('organization_id');

            $.ajax({
                url: '{$setDefaultPaymentMethodUrl}',
                async: false,
                type: 'POST',
                data: {
                    paymentMethodId: paymentMethodId,
                    organizationId: organizationId
                },
                success: function(response) {
                    Swal.fire({
                        title: "Default Payment Method Updated",
                        text: "Default payment method updated successfully",
                        icon: "success"
                    });

                    window.location.reload();

                    $(".remove-payment-method:not(.fund-transfer)").removeClass('hidden');
                    $("#removecard_" + paymentMethodId).addClass('hidden');
                },
                error: function(xhr, status, error) {
                    console.error('Error updating default payment method: ' + error);
                    Swal.fire({
                        title: "Error",
                        text: "There was an error updating the default payment method",
                        icon: "error"
                    });

                    window.location.reload();
                }
            });
        });

        $(document).on('click', '.remove-payment-method:not(.fund-transfer)', function() {

            Swal.fire({
                title: "Do you want to remove the Payment Method?",
                showDenyButton: true,
                showCancelButton: false,
                confirmButtonText: "Yes",
                denyButtonText: "No"
            }).then((result) => {
                if (result.isConfirmed) 
                {
                    var paymentMethodId = $(this).data('id');
                    var organizationId = $(this).data('organization_id');

                    $.ajax({
                        url: '{$removePaymentMethodUrl}',
                        async: false,
                        type: 'POST',
                        data: {
                            paymentMethodId: paymentMethodId,
                            organizationId: organizationId
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
        </script>
        SCRIPT;

		return $str;
	}

	public function removePaymentMethod($paymentMethodId)
	{
		$this->stripe->paymentMethods->detach($paymentMethodId);
	}

	public function getDefaultPaymentMethod()
	{
		$customer = $this->getStripeCustomer();

		return $customer->invoice_settings->default_payment_method;
	}

	public function getAllInvoices()
	{
	    if (!empty($this->organization->stripe_customer_id))
	    {
	        try
	        {
	            if (isset($this->stripe)) 
	            {
	                $invoices = $this->stripe->invoices->all(['customer' => $this->organization->stripe_customer_id]);

	                $sql = "SELECT stripe_invoice_id, payment_method, attachment, attachment_comment, plan_title, final_amount, payment_status, plan_period_start, plan_period_end
	                FROM sam_organization_subscriptions
	                WHERE organization_id = " . $this->organization->id;

	                $subscriptions = $query = $this->CI->db->query($sql)->result();

	                $subscriptionData = [];

					foreach ($subscriptions as $subscription) 
					{
					    $subscriptionData[$subscription->stripe_invoice_id] = $subscription;
					}

					foreach ($invoices->data as &$invoice) 
					{
					    $invoiceId = $invoice->id;
					    $invoice->subscription_data = null;

					    if (isset($subscriptionData[$invoiceId])) 
					    {
					        $invoice->subscription_data = $subscriptionData[$invoiceId];
					    }
					}

	                return $invoices;
	            }
	            else 
	            {
	                log_message('error', 'Stripe library is not initialized.');
	            }
	        } 
	        catch (\Exception $e) 
	        {
	            log_message('error', 'Error retrieving Invoices: ' . $e->getMessage());
	        }
	    } 
	    else 
	    {
	        log_message('error', 'Stripe customer ID is not set.');
	    }

	    return null;
	}

	public function cancelSubscription()
	{
		if (!empty($this->subscription->stripe_subscription_id))
		{
			if ($this->subscription->status == 'active')
			{
				$subscription = $this->stripe->subscriptions->update(
				  	$this->subscription->stripe_subscription_id,
				  	['cancel_at_period_end' => true]
				);
			}
		}

		$result = $this->updateOrganizationSubscription([
			'where' => ['id = ' . $this->subscription->id],
			'data' => ['auto_renew' => false]
		]);

		$this->updateSubscriptionLog(['auto_renew' => 0]);

		$this->setOrganizationData();
	}

	public function reactivateSubscription()
	{
		if (!empty($this->subscription->stripe_subscription_id))
		{
			if ($this->subscription->status == 'active')
			{
				$subscription = $this->stripe->subscriptions->update(
				  	$this->subscription->stripe_subscription_id,
				  	['cancel_at_period_end' => false]
				);
			}
		}

		$result = $this->updateOrganizationSubscription([
			'where' => ['id = ' . $this->subscription->id],
			'data' => ['auto_renew' => true]
		]);

		$this->updateSubscriptionLog(['auto_renew' => 1]);

		$this->setOrganizationData();
	}

	public function updatePaymentStatus($payment_status)
	{
		try
		{
			if(!empty($this->subscription->stripe_invoice_id))
			{
				$upcomingPlan = $this->getUpcomingPlan();
				$invoideId = empty($upcomingPlan) ? $this->subscription->stripe_invoice_id : $upcomingPlan['stripe_invoice_id'];

				$invoice = $this->stripe->invoices->retrieve($invoideId);

				if ($payment_status == 'paid')
				{
					$this->stripe->invoices->pay($invoice->id, [
					    'paid_out_of_band' => true
					]);
				}
				elseif($payment_status == 'cancelled')
				{
					$this->stripe->invoices->voidInvoice($invoice->id);
				}
				elseif($payment_status == 'refunded') //fund_tra invoices cant'be refunded.(will work for stripe)
				{
					// $paymentIntentId = $invoice->payment_intent;
					// $chargeId = $invoice->charge;

					// if (!empty($paymentIntentId)) 
					// {
					//     $refund = $this->stripe->refunds->create([
					//         'payment_intent' => $paymentIntentId,
					//     ]);
					// }
					// elseif (!empty($chargeId)) 
					// {
					//     $refund = $this->stripe->refunds->create([
					//         'charge' => $chargeId,
					//     ]);
					// } 
					// else 
					// {
					//     throw new Exception("No valid payment intent or charge found for this invoice.");
					// }
				}

				

				if (empty($upcomingPlan))
				{
					$result = $this->updateOrganizationSubscription([
						'where' => ['id = ' . $this->subscription->id],
						'data' => ['payment_status' => $payment_status]
					]);
				}
				else
				{
					$result = $this->updateOrganizationSubscription([
						'where' => ['id = ' . $upcomingPlan['id']],
						'data' => ['payment_status' => $payment_status]
					]);

					$result = $this->updateOrganizationSubscription([
						'where' => ['id = ' . $this->subscription->id],
						'data' => ['status' => 'inactive']
					]);

					if ($payment_status == 'paid')
					{
						$this->updateOrganization([
				        	'where' => ['id = ' . $this->organization->id],
				        	'data' => ['subscription_id' => $upcomingPlan['id']]
				        ]);
					}
				}

				$this->updateSubscriptionLog(['payment_status' => ucfirst($payment_status)]);

				$this->setOrganizationData();


			}
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	public function updateOwnerUser($newou_email,$requested_by,$status)
	{
		try
		{
			if($status == "Pending")
			{
				$details= "Request sent to ".$newou_email." by ".$requested_by.".<br> Status : ".$status;
				$this->updateSubscriptionLog(['change_of_ou' => $details]);
			}
			else if($status == "Cancelled")
			{
				$details= "Request sent to ".$newou_email." has been cancelled due to no action taken within 48 hours.<br> Status : ".$status;
				$this->updateSubscriptionLog(['change_of_ou' => $details]);
			}
			else
			{
				$details= $newou_email." has accepted the change of owner request.<br> Status : ".$status;
				$this->updateSubscriptionLog(['change_of_ou' => $details]);
			}
			
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	public function updateStatus($status)
	{
		$result = $this->updateOrganizationSubscription([
			'where' => ['id = ' . $this->subscription->id],
			'data' => ['status' => $status]
		]);

		$this->updateSubscriptionLog(['subscription_status' => ucfirst($status)]);
		$this->setOrganizationData();
	}

	public function updateOrganization($params)
	{
	    $where = '';
	    $data = '';

	    if (!empty($params['where'])) 
	    {
	        $whereArray = array_map(function($condition) {
	            return $this->CI->db->escape_str($condition);
	        }, $params['where']);

	        $where = implode(' AND ', $whereArray);
	    }

	    if (!empty($params['data'])) 
	    {
	        $dataArray = array_map(function($key, $value) {
	            return "$key = " . $this->CI->db->escape($value);
	        }, array_keys($params['data']), $params['data']);

	        $data = implode(', ', $dataArray);
	    }

	    if (!empty($data) && !empty($where)) 
	    {
	        $sql = "UPDATE sam_organizations
	                SET $data
	                WHERE $where";

	        return $this->CI->db->query($sql);
	    }

	    return false;
	}

	public function updateOrganizationSubscription($params)
	{
	    $where = '';
	    $data = '';

	    if (!empty($params['where'])) 
	    {
	        $whereArray = array_map(function($condition) {
	            return $this->CI->db->escape_str($condition);
	        }, $params['where']);

	        $where = implode(' AND ', $whereArray);
	    }

	    if (!empty($params['data'])) 
	    {
	        $dataArray = array_map(function($key, $value) {
	            return "$key = " . $this->CI->db->escape($value);
	        }, array_keys($params['data']), $params['data']);

	        $data = implode(', ', $dataArray);
	    }

	    if (!empty($data) && !empty($where)) 
	    {
	        $sql = "UPDATE sam_organization_subscriptions
	                SET $data
	                WHERE $where";

	        return $this->CI->db->query($sql);
	    }

	    return false;
	}

	public function getPlans($params = ['where' => ["p.status = '1'"]])
    {
        $defaults = array(
            'select' => '*',
            'where' => array(),
            'row' => 0,
            'order_by' => 'amount',
        );

        $params = array_merge($defaults, $params);

        $where = '';

        if (!empty($params['where'])) 
        {
            $where = implode(' AND ', $params['where']);
        }

        $sql = "SELECT " . $params['select'] . " 
                FROM (
                    SELECT p.*,
                    c.name AS currency_name,
                    c.code,
                    c.symbol
                    FROM sam_plans p
                    LEFT JOIN sam_currencies c ON p.currency_id = c.id
                ) AS p";

        if ($where != '') {
            $sql .= ' WHERE ' . $where;
        }

        if (isset($params['order_by']) && $params['order_by'] != '') {
            $sql .= ' ORDER BY ' . $params['order_by'];
        }

        $query = $this->CI->db->query($sql);

        if ($params['row']) 
        {
            return $query->row();
        } 
        else 
        {
            return $query->result();
        }
    }

    public function getSubscriptions($params = [])
    {
        $defaults = array(
            'select' => '*',
            'where' => array(),
            'row' => 0,
            'order_by' => 'id',
        );

        $params = array_merge($defaults, $params);

        $where = '';

        if (!empty($params['where'])) 
        {
            $where = implode(' AND ', $params['where']);
        }

        $sql = "SELECT " . $params['select'] . " FROM sam_organization_subscriptions s";

        if ($where != '') {
            $sql .= ' WHERE ' . $where;
        }

        if (isset($params['order_by']) && $params['order_by'] != '') {
            $sql .= ' ORDER BY ' . $params['order_by'];
        }

        $query = $this->CI->db->query($sql);

        if ($params['row']) 
        {
            return $query->row();
        } 
        else 
        {
            return $query->result();
        }
    }

    public function prorateAmount($newPlanId, $prorationDate)
    {
		if ($this->subscription->stripe_subscription_id)
		{
			$currentSubscription = $this->getStripeSubscription();
			if ($this->subscription->stripe_subscription_id && $currentSubscription->status == 'active')
			{
				return $this->calculateProrateAmountStripe($newPlanId, $prorationDate);
			}
			else
			{
				return $this->calculateProrateAmount($newPlanId, $prorationDate);
			}
		}
    	else
		{
			return $this->calculateProrateAmount($newPlanId, $prorationDate);
		}
    }

	public function calculateProrateAmount($newPlanId, $prorationDate)
	{
	    $newPlan = $this->getPlans(['where' => ['id = ' . (int)$newPlanId], 'row' => 1]);

	    $billingCycleStart = strtotime($this->subscription->plan_period_start);
	    $billingCycleEnd = strtotime($this->subscription->plan_period_end);

	    $totalDays = ($billingCycleEnd - $billingCycleStart) / 86400;
	    $remainingDays = ($billingCycleEnd - $prorationDate) / 86400;

	    $proratedCurrentRemaining = -abs(round(($this->plan->amount / $totalDays) * $remainingDays, 2));
	    $proratedNewRemaining = round(($newPlan->amount / $totalDays) * $remainingDays, 2);

	    $proration = (object) [
	        'amount_due' => $proratedNewRemaining - $proratedCurrentRemaining + $newPlan->amount,
	        'current_due' => $current_due,
	        'current_plan' => (object) [
	        	'plan_title' => $this->plan->plan_title,
	        	'code' => $this->plan->currency->code,
	            'amount' => $proratedCurrentRemaining,
	        	'description' => 'Unused time on Subscription Plan: ' . $this->plan->plan_title . ' after ' . date('d M Y', $prorationDate)
	        ],
	        'new_plan' => (object) [
	        	'plan_title' => $newPlan->plan_title,
	        	'code' => $newPlan->code,
	            'amount' => $proratedNewRemaining,
	        	'description' => 'Remaining time on Subscription Plan: ' . $newPlan->plan_title . ' after ' . date('d M Y', $prorationDate)
	        ],
	    ];

	    $proration->current_due = $proration->current_plan->amount + $proration->new_plan->amount;

	    return $proration;
	}


    public function calculateProrateAmountStripe($newPlanId, $proration_date) 
	{
	    $currentSubscription = $this->getStripeSubscription();

	    $newPlan = $this->getPlans(['where' => ['id = ' . $newPlanId], 'row' => 1]);

	    $items = [
	        [
	            'id' => $currentSubscription->items->data[0]->id,
	            'price' => $newPlan->plan_id,
	        ],
	    ];

	    $invoice = \Stripe\Invoice::upcoming([
	        'customer' => $this->organization->stripe_customer_id,
	        'subscription' => $this->subscription->stripe_subscription_id,
	        'subscription_items' => $items,
	        'subscription_proration_date' => $proration_date,
	    ]);

	    $proration = (object) [
	        'amount_due' => $invoice->amount_due / 100,
	        'current_plan' => (object) [
	        	'plan_title' => $this->plan->plan_title,
	        	'code' => $this->plan->currency->code,
	            'amount' => $invoice->lines->data[0]->amount / 100,
	            'description' => 'Unused time on Subscription Plan: ' . $this->plan->plan_title . ' after ' . date('d M Y', $proration_date),
	        ],
	        'new_plan' => (object) [
	        	'plan_title' => $newPlan->plan_title,
	        	'code' => $newPlan->code,
	            'amount' => $invoice->lines->data[1]->amount / 100,
	            'description' => $invoice->lines->data[1]->description,
	        ],
	    ];

	    $proration->current_due = $proration->current_plan->amount + $proration->new_plan->amount;

	    return $proration;
	}

	public function updateSubscription($newPlanId, $proration_date)
	{
		$oldPlan = $this->plan;
		$newPlan = $this->getPlans(['where' => ['id = ' . $newPlanId], 'row' => 1]);

		$current_payment_method = $this->organization->current_payment_method;

		$proration = $this->prorateAmount($newPlanId, $proration_date);

		if ($this->organization->current_payment_method == 'fund_transfer')
		{
			$this->updateSubscriptionWithBankTransfer($newPlan, $proration, $proration);
		}
		else
		{
			$this->updateSubscriptionWithStripe($newPlan, $proration_date, $proration, $proration);
		}


		$this->updateSubscriptionLog(['update_plan' => ['newPlan' => $newPlan, 'oldPlan' => $oldPlan, 'current_payment_method' => $current_payment_method,  'proration' => $proration]]);

		$this->setOrganizationData();
	}

	public function getPlanServices($plan)
	{
		$planServices = new \stdClass;
        $planServices->onboarding_support = $plan->onboarding_support;
        $planServices->carbon_footprint_report = $plan->carbon_footprint_report;
        $planServices->customizations = $plan->customizations;
        $planServices->customer_service_support = $plan->customer_service_support;
        $planServices->aI_tool = $plan->aI_tool;

        return $planServices;
	}

	public function createCustomInvoice($amount, $code, $description)
	{
		$organization_id = $this->organization_id;

		$compDetails = $this->CI->mdl->getActiveCompanies([
			'select' => 'id, company_name, company_id, address, address2, postal_code, state, com_add_country_id',
			'where' => [
				"cw.organization_id = $organization_id",
				"cw.is_deleted = 0",
				"cw.status = 1"
			]
		]);

		usort($compDetails, function($a, $b) {
			return $a['company_id'] - $b['company_id'];
		});

		$oldestCompany = $compDetails[0];

		$orgs = ORM::for_table('sam_organizations')
						->where('id', (int)$organization_id)
						->where('status','1')
						->find_one();
	
		if($oldestCompany)
		{
			$country = ORM::for_table('sam_countries')->where('id', $oldestCompany['com_add_country_id'])->find_one();
        	$this->company_country = $country->name;

			$customer = \Stripe\Customer::retrieve($this->organization->stripe_customer_id);	
			$customer->email = $orgs->email;
			$customer->address =  [
				'line1' => $oldestCompany['address'],
				'line2' => $oldestCompany['address2'], // Optional
				'state' => $oldestCompany['state'],
				'postal_code' => $oldestCompany['postal_code'],
				'country' => $country->name
			];
			$customer->save();

			$comp_name = $oldestCompany['company_name'];

			$invoice = $this->stripe->invoices->create([
				'customer' => $this->organization->stripe_customer_id,
				'custom_fields' => [
					[
						'name' => 'Company Name',
						'value' => ucwords($comp_name),  // Assuming $comp_name holds the company name
					],
				],
				'auto_advance' => false
			]);
		}
		else
		{
			$invoice = $this->stripe->invoices->create([
				'customer' => $this->organization->stripe_customer_id,
				'auto_advance' => false
			]);
		}

		$this->stripe->invoiceItems->create([
		    'customer' => $this->organization->stripe_customer_id,
		    'amount' => (int) $amount * 100,
		    'currency' => strtoupper($code),
		    'description' => $description,
		    'invoice' => $invoice->id
		]);

		$finalizedInvoice = $invoice->finalizeInvoice($invoice->id);

		return $finalizedInvoice;
	}

	public function updateSubscriptionWithBankTransfer($newPlan, $proration, $data = null)
	{
		$planServices = $this->getPlanServices($newPlan);

		$subscription_data = array(
            'organization_id' => $this->organization->id,
            'plan_id' => $newPlan->id,
            'payment_method' => 'fund_transfer',
            'stripe_subscription_id' => null,
            'stripe_invoice_id' => null,
            'stripe_plan_id' => $newPlan->plan_id,
            'plan_title' => $newPlan->plan_title,
            'plan_description' => $newPlan->plan_description,
            'maximum_no_of_users' => $newPlan->maximum_no_of_users,
            'plan_services' => json_encode($planServices),
            'plan_amount' => $newPlan->amount,
            'final_amount' => $proration->current_due,
            'plan_amount_currency' => $newPlan->code, 
            'plan_interval' => 'year', 
            'plan_interval_count' => $newPlan->number_of_year,
            'promo_code' => null,
            'plan_period_start' => $this->subscription->plan_period_start,
            'plan_period_end' => $this->subscription->plan_period_end,
            'payer_email' => $this->organization->email,
            'created_at' => date("Y-m-d H:i:s"),
            'status' => 'upcoming',
            'payment_status' => 'pending',
            'data' => $data ? json_encode($data) : null
        );

		$this->CI->db->set($subscription_data);
        $this->CI->db->insert('sam_organization_subscriptions');

        $new_subscription_id = $this->CI->db->insert_id();

        $invoice = $this->createCustomInvoice($proration->current_due, $newPlan->code, 'Subscription Plan upgraded from ' . $this->plan->plan_title . ' to ' . $newPlan->plan_title);

        // updating new subscription data
		$this->updateOrganizationSubscription([
        	'where' => ['id = ' . $new_subscription_id],
        	'data' => [
        		'stripe_invoice_id' => $invoice->id, 
        		'final_amount' => ($invoice->amount_due / 100)
        	]
        ]);

        if ($this->subscription->stripe_subscription_id)
        {
        	$this->stripe->subscriptions->cancel(
			    $this->subscription->stripe_subscription_id
			);
        }

        // updating old subscription data
        $this->updateOrganizationSubscription([
        	'where' => ['id = ' . $this->subscription->id],
        	'data' => [
        		'comments' => 'Update plan requested for plan ' . $newPlan->plan_title
        	]
        ]);

        // $this->updateOrganization([
        // 	'where' => ['id = ' . $this->organization->id],
        // 	'data' => ['subscription_id' => $new_subscription_id]
        // ]);
	}

	public function updateSubscriptionWithStripe($newPlan, $proration_date, $proration, $data = null)
	{
		if ($this->subscription->stripe_subscription_id)
		{
			$currentSubscription = $this->getStripeSubscription();

			if ($this->subscription->stripe_subscription_id && $currentSubscription->status == 'active')
			{
				//$currentSubscription = $this->getStripeSubscription();

				$newSubscription = $this->stripe->subscriptions->update(
					$this->subscription->stripe_subscription_id,
					[
						'items' => [
							[
								'id' => $currentSubscription->items->data[0]->id,
								'price' => $newPlan->plan_id,
							],
						],
						'proration_date' => $proration_date,
					]
				);

				$invoice = $this->createSubscriptionInvoice($newSubscription['id']);
			}
			else
			{
				$newSubscription = $this->stripe->subscriptions->create([
					"customer" => $this->organization->stripe_customer_id,
					"items" => [["plan" => $newPlan->plan_id]],
					"expand" => ["latest_invoice"],
				]);

				$invoice = $newSubscription->latest_invoice;

				// $invoice = $this->createCustomInvoice($proration->current_due, $newPlan->code, 'Plan upgraded from ' . $this->plan->plan_title . ' to ' . $newPlan->plan_title);
				// $this->stripe->invoices->pay($invoice->id);
			}
		}
		else
		{
			$newSubscription = $this->stripe->subscriptions->create([
                "customer" => $this->organization->stripe_customer_id,
	            "items" => [["plan" => $newPlan->plan_id]],
	            "expand" => ["latest_invoice"],
	        ]);

	        $invoice = $newSubscription->latest_invoice;

	        // $invoice = $this->createCustomInvoice($proration->current_due, $newPlan->code, 'Plan upgraded from ' . $this->plan->plan_title . ' to ' . $newPlan->plan_title);
	        // $this->stripe->invoices->pay($invoice->id);
		}
		
		$planServices = $this->getPlanServices($newPlan);

		$subscription_data = array(
			'organization_id' => $this->organization->id,
			'plan_id' => $newPlan->id,
			'payment_method' => 'stripe',
			'stripe_subscription_id' => $newSubscription['id'],
			'stripe_invoice_id' => $invoice->id,
			'stripe_plan_id' => $newSubscription->plan->id,
			'plan_title' => $newPlan->plan_title,
			'plan_description' => $newPlan->plan_description,
			'maximum_no_of_users' => $newPlan->maximum_no_of_users,
			'plan_services' => json_encode($planServices),
			'plan_amount' => ($newSubscription->plan->amount / 100),
			'final_amount' => $invoice->amount_due / 100,
			'plan_amount_currency' => $newSubscription->plan->currency,
			'plan_interval' => $newSubscription->plan->interval, 
			'plan_interval_count' => $newSubscription->plan->interval_count,
			'promo_code' => '',
			'plan_period_start' => date("Y-m-d H:i:s", $newSubscription->current_period_start),
			'plan_period_end' => date("Y-m-d H:i:s", $newSubscription->current_period_end),
			'payer_email' => $this->organization->email,
			'created_at' => date("Y-m-d H:i:s", $newSubscription->created),
			'status' => $newSubscription->status,
			'data' => json_encode(['subscription' => $newSubscription, 'proration' => $proration])
		);

		$this->CI->db->set($subscription_data);
		$this->CI->db->insert('sam_organization_subscriptions');

		$new_subscription_id = $this->CI->db->insert_id();

		// updating old subscription data
        $this->updateOrganizationSubscription([
        	'where' => ['id = ' . $this->subscription->id],
        	'data' => [
        		'status' => 'inactive', 
        		'comments' => 'Subscription Plan updated to ' . $newPlan->plan_title
        	]
        ]);

        $this->updateOrganization([
        	'where' => ['id = ' . $this->organization->id],
        	'data' => ['subscription_id' => $new_subscription_id]
        ]);
	}

	public function createSubscriptionInvoice($subscription_id)
	{
		$organization_id = $this->organization_id;

		/*$CompDetails = ORM::for_table('sam_companies')
						->where('organization_id', (int)$organization_id)
						->where('status','1')
						->where('is_deleted','0')
						->order_by_asc('company_id') // Order by 'id' in ascending order
						->find_one();*/	

		$compDetails = $this->CI->mdl->getActiveCompanies([
			'select' => 'id, company_name, company_id, address, address2, postal_code, state, com_add_country_id',
			'where' => [
				"cw.organization_id = $organization_id",
				"cw.is_deleted = 0",
				"cw.status = 1"
			]
		]);

		usort($compDetails, function($a, $b) {
			return $a['company_id'] - $b['company_id'];
		});

		$oldestCompany = $compDetails[0];

		$orgs = ORM::for_table('sam_organizations')
						->where('id', (int)$organization_id)
						->where('status','1')
						->find_one();
			
		if($oldestCompany)
		{
			$country = ORM::for_table('sam_countries')->where('id', $oldestCompany['com_add_country_id'])->find_one();
        	$this->company_country = $country->name;

			$customer = \Stripe\Customer::retrieve($this->organization->stripe_customer_id);	
			$customer->email = $orgs->email;
			$customer->address =  [
				'line1' => $oldestCompany['address'],
				'line2' => $oldestCompany['address2'], // Optional
				'state' => $oldestCompany['state'],
				'postal_code' => $oldestCompany['postal_code'],
				'country' => $country->name
			];
			$customer->save();

			$comp_name = $oldestCompany['company_name'];
			//$country = ORM::for_table('sam_countries')->where('id', $CompDetails->com_add_country_id)->find_one();

			$invoice = $this->stripe->invoices->create([
				'customer' => $this->organization->stripe_customer_id,
				'custom_fields' => [
								[
									'name' => 'Company Name',
									'value' => ucwords($comp_name),
								]],
				'subscription' => $subscription_id,
			]);
		}
		else
		{
			$invoice = $this->stripe->invoices->create([
					'customer' => $this->organization->stripe_customer_id,
					'subscription' => $subscription_id,
				]);
		}		

		$invoiceObject = $this->stripe->invoices->retrieve($invoice->id);
		$invoiceObject->pay();

		return $invoice;
	}

	protected function getOrganizationId()
	{
		return $this->CI->session->userdata('userloginid');
	}

	public static function getExpiringSubscriptions($hour = 1, $current_payment_method = 'fund_transfer')
	{
		$currentTime = date('Y-m-d H:i:s');
		$expiryTime = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
	}

	public function getUpcomingPlan()
	{
		$sql = "SELECT * 
		FROM sam_organization_subscriptions
		WHERE organization_id = " . $this->organization->id . " AND 
		status = 'upcoming'";

		$subscription = $query = $this->CI->db->query($sql)->row_array();

		return $subscription;
	}

	public function updateSubscriptionLog($logData)
	{
		$current_user = $this->getCurrentUser();
		$data = array(
			'organization_id' => !empty($this->organization_id) ? $this->organization_id : $this->CI->session->userdata('organization_id'),
			'subscription_id' => !empty($this->subscription_id) ? $this->subscription_id : $this->CI->session->userdata('subscription_id'),
			'data' => json_encode($logData),
			'user_type' => !empty($current_user['user_type']) ? $current_user['user_type'] : 'Cron Job',
			'created_by' => !empty($current_user['user_id']) ? $current_user['user_id'] : '1',//for cron job
			'created_at' => date("Y-m-d H:i:s"),
		);

		$this->CI->db->set($data);
        $this->CI->db->insert('sam_organization_subscriptions_audit_log');
	}

	public function getAuditLogs()
	{
		// $result = $this->CI->db->where('organization_id', $this->organization_id)
		// 			->order_by('created_at', 'DESC')
		// 			->get('sam_organization_subscriptions_audit_log')
		// 			->result_array();

		/*$result = $this->CI->db
			    ->select('audit.*, 
			              IF(audit.user_type = "admin", admin.id, org.id) AS user_id,
			              IF(audit.user_type = "admin", admin.name, org.first_name) AS user_name,
			              IF(audit.user_type = "admin", admin.lastname, org.last_name) AS user_lastname')
			    ->from('sam_organization_subscriptions_audit_log AS audit')
			    ->join('sam_admin AS admin', 'admin.id = audit.created_by AND audit.user_type = "admin"', 'left')
			    ->join('sam_organizations AS org', 'org.id = audit.created_by AND audit.user_type = "org_user"', 'left')
			    ->where('audit.organization_id', $this->organization_id)
			    ->order_by('audit.created_at', 'DESC')
			    ->get()
			    ->result_array();*/
		$result = $this->CI->db
				->select('audit.*, 
						  IF(audit.user_type = "admin", admin.id, usr.user_id) AS user_id,
						  IF(audit.user_type = "admin", admin.name, usr.first_name) AS user_name,
						  IF(audit.user_type = "admin", admin.lastname, usr.last_name) AS user_lastname')
				->from('sam_organization_subscriptions_audit_log AS audit')
				->join('sam_admin AS admin', 'admin.id = audit.created_by AND audit.user_type = "admin"', 'left')
				->join(
					"(SELECT su.*
					   FROM sam_user su
					   INNER JOIN (
						   SELECT user_id, MAX(id) AS max_id
						   FROM sam_user
						   WHERE organization_id = {$this->organization_id}
						   GROUP BY user_id
					   ) latest ON su.user_id = latest.user_id AND su.id = latest.max_id
					 ) AS usr",
					'usr.user_id = audit.created_by AND audit.user_type = "org_user"',
					'left'
				)
				->where('audit.organization_id', $this->organization_id)
				->order_by('audit.created_at', 'DESC')
				->get()
				->result_array();

		//	echo "<pre>"; print_r($result); die;
		//echo print_r($result);exit;
		foreach($result as $key => $value)
		{
			$html = '';
			$logs = json_decode($value['data'], true);
			$result[$key]['logs'] = $logs;

			$logKey = array_keys($logs)[0] ?? '';

			if ($logKey == 'default_payment_method')
			{
				$html .= ($logKey == 'default_payment_method') 
    					? '<strong>Default Payment Method Updated : </strong>' 
        				. str_replace(['stripe', 'fund_transfer'], ['Stripe', 'Fund Transfer'], $logs[$logKey]) 
    					: '';
			}
			else if ($logKey == 'update_plan')
			{
				$html .= '<strong>Plan Updated : </strong>' . $logs[$logKey]['oldPlan']['plan_title'];
				$html .= ' to ';
				$html .=  $logs[$logKey]['newPlan']['plan_title'];
			}
			else if ($logKey == 'status')
			{
				$html .= '<strong>Status Updated : </strong>' . $logs[$logKey];
			}
			else if ($logKey == 'subscription_status')
			{
				$html .= '<strong>Subscription Status Updated : </strong>' . $logs[$logKey];
			}
			else if ($logKey == 'payment_status')
			{
				$html .= '<strong>Payment Status Updated : </strong>' . $logs[$logKey];
			}
			else if ($logKey == 'auto_renew')
			{
				$html .= '<strong>Auto Renew : </strong>' . ($logs[$logKey] == 0 ? 'Cancelled' : 'Activated');
			}
			else if ($logKey == 'change_of_ou')
			{
				$html .= '<strong>Change of OU : </strong>' . $logs[$logKey];
			}
			else if ($logKey == 'plan_renewal')
			{
				$html .= '<strong>Plan Renewal : </strong>' . $logs[$logKey];
			}
			else
			{
				$html .= '<strong>' . $logKey . ' : </strong>' . $logs[$logKey];
			}
			
		    $result[$key]['html'] = $html;

			$userType = $value['user_type'];
			$userName ="";
			if (empty($value['user_id']))
			{
				$oldou_details = $this->CI->db
					->select('log.currentou_user_id, su.first_name, su.last_name')
					->from("(SELECT log.currentou_user_id, log.organization_id 
							FROM sam_change_of_ou_log AS log 
							WHERE log.organization_id = {$this->organization_id} 
							ORDER BY id 
							LIMIT 1) AS log")
					->join('sam_user AS su', 'log.currentou_user_id = su.user_id')
					->order_by('su.id')
					->limit(1)
					->get()
					->result_array();
				
				if($oldou_details)
				{
					foreach ($oldou_details as $oldou_detail) {
						$firstname = $oldou_detail['first_name'];
						$lastname = $oldou_detail['last_name'];
					}
					$userName = ucfirst($firstname) . ' ' . ucfirst($lastname);
				
				}	
				else
				{
					$oldous = $this->CI->db
					->select('org.first_name, org.last_name')
					->from('sam_organizations AS org')
					->where('org.id', $this->organization_id) 
					->limit(1)
					->get()
					->result_array();

					if($oldous)
					{
						foreach ($oldous as $oldou) {
							$firstname = $oldou['first_name'];
							$lastname = $oldou['last_name'];
						}
					}

					$userName = ucfirst($firstname) . ' ' . ucfirst($lastname);
				}

				$result[$key]['user_type'] = ($userType == 'admin') ? 'Super Admin ('.$userName.')' : 'Owner User ('.$userName.')';
				$result[$key]['user_tooltip'] = (!empty($userName)) ? $userName : 'Unknown User';
			}
			else
			{
				$userName = ucfirst($value['user_name']) . ' ' . ucfirst($value['user_lastname']);

		    	$result[$key]['user_type'] = ($userType == 'admin') ? 'Super Admin ('.$userName.')' : 'Owner User ('.$userName.')';
		   	 	$result[$key]['user_tooltip'] = (!empty($userName)) ? $userName : 'Unknown User';
			}    
			
		}
		return $result;
	}

	public function getCurrentUser()
	{
		$data = array();

		if (!empty($this->CI->session->userdata('loginid')))
		{
			$data['user_type'] = 'admin';
			$data['user_id'] = $this->CI->session->userdata('loginid');
		}
		else
		{
			$data['user_type'] = $this->CI->session->userdata('user_type');
			$data['user_id'] = $this->CI->session->userdata('logged_in_user_id');
		}

		return $data;
	}
}