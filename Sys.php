<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/models/Subscription.php');

class Sys extends MY_ORG_Controller {

    public $_countries = 'sam_countries';
    public $_statesTable = 'sam_states';

    function __construct()
    {
        parent::__construct();
        
        $this->load->library('form_validation');
        $this->load->helper('date');

        $this->load->model('mdl_site','mdl');
        //$this->load->model('subscription_model','subscription');
    }


    public function myaccount() 
    {

        $meta['page_title'] = lang('My Account');
        $meta['menu'] = 'myaccount';

        $this->form_validation->set_rules('salutation','enter salutation','required|trim');
        $this->form_validation->set_rules('first_name','enter first name','required|trim');
        $this->form_validation->set_rules('last_name','enter last name','required|trim');
        $this->form_validation->set_rules('email','enter email','required|trim');
        //$this->form_validation->set_rules('mobile_country_id','select mobile country','required|trim');
        //$this->form_validation->set_rules('state','select state','required|trim');
        $this->form_validation->set_rules('job_title','enter job_title','required|trim');

        $this->form_validation->set_rules('effective_date','select effective_date','required|trim');

        $this->form_validation->set_rules('mobile_country_id','select mobile country','required|trim');
        $this->form_validation->set_rules('mobile_number','enter mobile number','required|trim');

        if(isset($_POST['newpwd']) && $_POST['newpwd'] != "" ){
            $this->form_validation->set_rules('newpwd', 'Password', 'required');
            $this->form_validation->set_rules('password', 'Confirm Password', 'required|matches[newpwd]');
        }    


        if ($this->input->server('REQUEST_METHOD') == 'POST' && $this->form_validation->run() == true) {

            $user_id = $this->session->userdata('userloginid');

            $d = ORM::for_table('sam_organizations')->where('id',$user_id)
            ->find_one();

            $data = [
                'salutation'      => $_POST['salutation'],
                'first_name'     => $_POST['first_name'],
                'last_name'      => $_POST['last_name'],
                'email'      => $_POST['email'],
                'effective_date'   => $_POST['effective_date'],
                    //'country_id'   => $_POST['country_id'],
                'status'   => $_POST['status'],
                'job_title'   => $_POST['job_title'],
                'off_add_country_id'   => $_POST['off_add_country_id'],
                'state'      => $_POST['state'],
                'postal_code'   => $_POST['postal_code'],
                'address'   => $_POST['address'],
                'off_did_country_id'   => $_POST['off_did_country_id'],
                'area_code'   => $_POST['area_code'],
                'desk_phone_number'   => $_POST['desk_phone_number'],
                'mobile_country_id'   => $_POST['mobile_country_id'],
                'mobile_number'   => $_POST['mobile_number'],

            ];

            if(isset($_POST['newpwd']) && $_POST['newpwd'] != "" ){
                $data['password'] = md5($_POST['newpwd']);
            }

            if($this->mdl->update('sam_organizations',$user_id,$data)) {
                $this->session->set_flashdata('success',lang('recupdatesuccess'));
                $this->sam->system_logs_entry('success',$this->session->userdata('otp_mail_id'),'Update Profile',$this->session->userdata('otp_mail_id')." have updated their profile.");
                redirect('org/myaccount');
            } else {
                $this->session->set_flashdata('error',lang('recupdatef'));
                $this->sam->system_logs_entry('error',$this->session->userdata('otp_mail_id'),'Update Profile',$this->session->userdata('otp_mail_id')." have try to update profile.");
                redirect('org/myaccount');
            }
        }else{
            $user_id = $this->session->userdata('userloginid');

            $this->data['sam'] = $this->mdl->get('sam_organizations',$user_id);

            $_sql = "SELECT * FROM sam_countries";       
            $this->data['all_countries'] = $this->mdl->get_raw($this->_countries,$_sql);
            $this->session->set_flashdata('success', '');
            $this->session->set_flashdata('error', '');
            $this->page_construct('settings/myaccount', $meta, $this->data);
        }


    }

    public function notification_list(){

        $this->load->library('session');
        $this->load->database();

        $meta['page_title'] = lang('All Notification');
        $meta['menu'] = 'notification';
        $this->data['status'] = 'none';
        $currentUserId = $this->session->userdata('userloginid');

        $_sql = "SELECT * FROM sam_notification WHERE user_role = 'org_user' AND user_id = $currentUserId ORDER BY created_at DESC";
        $this->data['user_notifications'] = $this->mdl->get_raw($this->_thisTable, $_sql);

        $this->page_construct('usersetting/notification_list', $meta, $this->data);
    }

    public function subscription() 
    {
        if ($this->user_type != 'org_user')
        {
            redirect('/dashboard');
        }

        $subscription = new Subscription();

        $organization = $subscription->getOrganizationData();

        $customer = $subscription->getStripeCustomer();

        $invoicedata = $organization->subscription;

        $paymentMethods = $subscription->renderPaymentMethods(); 

        $defaultPaymentMethodId = $customer->invoice_settings->default_payment_method;

        $invoices = $subscription->getAllInvoices();

        $upcomingPlan = $subscription->getUpcomingPlan();

        $this->data['organization'] = $organization->organization;
        $this->data['paymentMethods'] = $paymentMethods;
        $this->data['defaultPaymentMethodId'] = $defaultPaymentMethodId;
        $this->data['invoiceList'] = $invoices;
        $this->data['invoicedata'] = $invoicedata;
        $this->data['next_bill'] = $organization->subscription->plan_period_end;
        $this->data['sub_status'] = $organization->subscription->status;
        $this->data['subscription'] = $organization->subscription;
        $this->data['upcomingPlan'] = $upcomingPlan;
        $this->data['plan'] = $organization->plan;
        $this->data['audit_logs'] = $subscription->getAuditLogs();

        $meta['page_title'] = lang('Review Subscription Plan');
        $meta['section_label'] = lang('Subscription Plan');
        $meta['tab_title'] = lang('Subscription');
        $meta['plan_detail'] = lang('Plan Detail');
        $meta['menu'] = 'subscription';

        $change_status = $this->session->userdata('change_status');

        if($change_status != 'activate')
        {
            $this->session->set_flashdata('success', '');
            $this->session->set_flashdata('error', '');
        }
   
        $this->page_construct('settings/newsubscription', $meta, $this->data);
    }

    public function cancelSubscription()
    {
        try 
        {
            $subscription = new Subscription();
            $result = $subscription->cancelSubscription();

            $this->session->set_flashdata('cancel_subscription', 'success');

            echo json_encode(['status' => 'success']);
        }
        catch (Exception $e) 
        {
            $this->session->set_flashdata('cancel_subscription', 'error');
            $this->session->set_flashdata('cancel_subscription_message', 'Error occured while cancelling your subscription. please try again.');

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function retrieveData()
    {
        try 
        {
            $subscription = new Subscription();
            $result = $subscription->retrieveData();

            $this->session->set_flashdata('retrieve_data', 'success');

            echo json_encode(['status' => 'success']);
        }
        catch (Exception $e) 
        {
            $this->session->set_flashdata('retrieve_data', 'error');
            $this->session->set_flashdata('retrieve_data', 'Error occured while updating data. Please try again.');

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function reactivateSubscription()
    {
        try 
        {
            $subscription = new Subscription();
            $result = $subscription->reactivateSubscription();

            $this->session->set_flashdata('reactivate_subscription', 'success');

            echo json_encode(['status' => 'success']);
        }
        catch (Exception $e) 
        {
            $this->session->set_flashdata('reactivate_subscription', 'error');
            $this->session->set_flashdata('reactivate_subscription_message', 'Error occured while activating your subscription. please try again.');

            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function createPaymentMethod()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') 
        {
            $this->load->library('stripe');

            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);

            $organization_id = $this->session->userdata('userloginid');
            $organization = $this->mdl->get('sam_organizations', $organization_id);

            $customer_id = $organization->stripe_customer_id;
            $paymentMethodId = $_POST['paymentMethodId'];

            try 
            {
                $stripe->paymentMethods->attach(
                    $paymentMethodId,
                    ['customer' => $customer_id]
                );

                $this->session->set_flashdata('payment_method_added', 'success');

                echo json_encode(['status' => 'success', 'message' => 'Payment method attached.']);
            }
            catch (\Stripe\Exception\CardException $e) 
            {
                $this->session->set_flashdata('payment_method_added', 'error');
                $this->session->set_flashdata('payment_method_error_message', $e->getMessage());

                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            catch (\Stripe\Exception\ApiErrorException $e) 
            {
                $this->session->set_flashdata('payment_method_added', 'error');
                $this->session->set_flashdata('payment_method_error_message', $e->getMessage());

                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
        else 
        {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payment method ID.']);
        }
    }

    public function setDefaultPaymentMethod()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
            try 
            {
                $paymentMethodId = $_POST['paymentMethodId'];

                $subscription = new Subscription();
                $subscription->setDefaultPaymentMethod($paymentMethodId);

                echo json_encode(['status' => 'success']);
            }
            catch (Exception $e) 
            {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    }

    public function removePaymentMethod()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {    
            try 
            {
                $paymentMethodId = $_POST['paymentMethodId'];

                $subscription = new Subscription();

                $subscription->removePaymentMethod($paymentMethodId);

                echo json_encode(['status' => 'success']);
            }
            catch (Exception $e) 
            {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }   
        }
    }

    public function change_subscription() 
    {
        if ($this->user_type != 'org_user')
        {
            redirect('/dashboard');
        }

        $subscription = new Subscription();
        $organization = $subscription->getOrganizationData();
        // "p.code = '" . $organization->subscription->plan_amount_currency . "'"
        $this->data['active_plans'] = $subscription->getPlans(['where' => ["p.status = '1'"]]);
        $this->data['active_subscription'] = $organization->subscription;
        $this->data['current_plan'] = $organization->plan;
        $this->data['stripe_customer_id'] = $organization->stripe_customer_id;
        $this->data['old_stripe_plan_id'] = $organization->subscription->stripe_plan_id;
        $this->data['old_subscription_id'] = $organization->subscriptionstripe_subscription_id;
        $meta['page_title'] = lang('Change Plan');
        $meta['menu'] = 'subscription';

        $plan_status = $this->session->userdata('plan_status');

        if($plan_status == 'expired_grace_period')
        {
            $meta['page_title'] = 'Renew Plan';
            $this->session->set_flashdata('success', '');
            $this->session->set_flashdata('error', 'Hi! Your subscription plan has expired. You are within the 60-day grace period. Please renew your plan to continue using the application without restrictions...');
            $this->page_construct('settings/renew-subscription', $meta, $this->data);
        }
        else
        {
            $this->session->set_flashdata('success', '');
            $this->session->set_flashdata('error', '');
            $this->page_construct('settings/change-subscription', $meta, $this->data);
        }
        
    }

    public function updateSubscription($newPlanId)
    {
        if ($this->user_type != 'org_user')
        {
            redirect('/dashboard');
        }
        
		$this->load->library('stripe');

		\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
		$stripe = new \Stripe\StripeClient(STRIPE_SECRET_KEY);
			
        $subscription = new Subscription();

        $selected_plan = $subscription->getPlans(['where' => ['id = ' . $newPlanId], 'row' => 1]);

        $organization = $subscription->getOrganizationData();
		$organization_id = $organization->subscription->organization_id;
		$subscription_id = $organization->subscription->id;
		//echo "<pre>"; print_r($organization_id); die;
		
		$previous_plan =  $organization->subscription->plan_title;
		$previous_payment_method = $organization->subscription->payment_method;
		$previous_payment_method = ($previous_payment_method === 'fund_transfer') ? 'Fund Transfer' : $previous_payment_method;

        $proration_date = time();

        $proration = $subscription->prorateAmount($newPlanId, $proration_date);

        $paymentMethods = $subscription->renderPaymentMethods(false, false);

        if ($_SERVER['REQUEST_METHOD'] === 'POST')
        {
			$sql = "SELECT * FROM sam_organizations WHERE id = '$organization_id' order by id desc limit 1";
                
            $orgData = $this->mdl->get_raw_array(null, $sql);
            $customer_stripe_id = $orgData[0]['stripe_customer_id'];
            $current_payment_method = $orgData[0]['current_payment_method'];
            $first_name = $orgData[0]['first_name'];
            $last_name = $orgData[0]['last_name'];
            $email = $orgData[0]['email'];

            if($current_payment_method == 'stripe')
            {
                $customer = \Stripe\Customer::retrieve($customer_stripe_id);
                //$customer = \Stripe\Customer::retrieve('cus_Rur21GJu2bY7FG');
                $paymentMethod = \Stripe\PaymentMethod::retrieve($customer->invoice_settings->default_payment_method);

                //$expMonth = $paymentMethod->card->exp_month;
                $expMonth = str_pad($paymentMethod->card->exp_month, 2, '0', STR_PAD_LEFT);
                $expYear = $paymentMethod->card->exp_year;
                $last4 = $paymentMethod->card->last4;

                $currentMonth = date('n');  // e.g., 5 for May
                $currentYear = date('Y');

                $isExpired = ($expYear < $currentYear) || ($expYear == $currentYear && $expMonth < $currentMonth);

                if ($isExpired) {
                    $expMonth1 = str_pad($paymentMethod->card->exp_month, 2, '0', STR_PAD_LEFT);
                    $monyear = $expMonth1.'/'.$expYear;

                    $message = array(
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'monyear' => $monyear,
                        'last4' => $last4
                    );

                    //call function send mail in libraries\Sam.php, pass slug name to find the email template (email id = 36)
                    $this->sam->send_mail('card_is_expired', $email, $first_name . ' ' . $last_name, $message);

                    $cardexpired = 'cardexpired';
                    $this->session->set_userdata('card_error', $cardexpired);
                    $this->session->set_userdata('card_error_message', 'Subscription Processing Error. <br>The default payment method, a card ending in <b>*'.$last4.'</b> has expired as of <b>'.$expMonth1.'/'.$expYear.'</b>. <br>Please update payment method details to proceed.');

                    $this->session->set_flashdata('success', '');
                    $this->session->set_flashdata('error', '');
                
                    redirect('subscription');
                } 
            }

				
            $proration_date = $_POST['proration_date'];

            //$subscription->updateSubscription($newPlanId, $proration_date);
			
			try {
				 $subscription->updateSubscription($newPlanId, $proration_date);
			} catch (\Exception $e) {
					$cardexpired = 'cardexpired';
                    $this->session->set_userdata('card_error', $cardexpired);
                    $this->session->set_userdata('card_error_message', $e->getMessage());

                    $this->session->set_flashdata('success', '');
                    $this->session->set_flashdata('error', '');
					
					$attachment_comment = 'Subscription Processing Error : '.$e->getMessage().' Kindly retry the payment.';
					
					$this->mdl->update('sam_organization_subscriptions',$subscription_id,$data);
					
					$this->db->where('id', $subscription_id)->update('sam_organization_subscriptions', array('attachment_comment' => $attachment_comment));
                
                    redirect('subscription');
			}

            //$newOrgData = $subscription->getOrganizationData();

            $paymentMethods = lang('payment_methods');
			
			$sql = "SELECT * FROM sam_organization_subscriptions WHERE organization_id = '$organization_id' order by id desc limit 1";
			$newOrgData = $this->mdl->get_raw($this->_thisTable, $sql);
			
			$new_plan = $newOrgData[0]['plan_title'];
			$payment_method = $paymentMethods[$newOrgData[0]['payment_method']];

            $plan_status = $this->session->userdata('plan_status');
            if($plan_status == 'expired_grace_period')
            {
                $message = array(
                    'first_name' => $organization->organization->first_name,
                    'last_name' => $organization->organization->last_name,
                    'previous_plan' => $previous_plan,
                    'previous_payment_method' => ucfirst($previous_payment_method),
                    'new_plan' => $new_plan,
                    'payment_method' => $payment_method
                );
    
                //echo "<pre>"; print_r($organization->organization);print_r($newOrgData->organization);print_r($message);die;
                //call function send mail in libraries\Sam.php, pass slug name to find the email template (email id = 21)
                $this->sam->send_mail('subscription_plan_renewal', $organization->organization->email, $organization->organization->first_name . ' ' . $organization->organization->last_name, $message);
    
                $plan_status = 'active';
                $this->session->set_userdata('plan_status', $plan_status);
                $this->session->set_userdata('change_status', 'activate');
    
                $this->session->set_flashdata('success', 'H! '. $organization->organization->first_name.' '. $organization->organization->last_name.' <br> Your subscription plan has been successfully renewed!');
                $this->session->set_flashdata('error', '');
            }
            else
            {
                if($this->session->userdata('subscription_status') == 'error')
                {
                    $error_message = $this->session->userdata('subscription_error_message');
                    $invoice_link = $this->session->userdata('invoice_link');
                    $message = array(
                        'first_name' => $organization->organization->first_name,
                        'last_name' => $organization->organization->last_name,
                        'message' => $error_message,
                        'link' => $invoice_link
                    );
    
                    //echo "<pre>"; print_r($organization->organization);print_r($newOrgData->organization);print_r($message);die;
                    //call function send mail in libraries\Sam.php, pass slug name to find the email template (email id = 21)
                    $this->sam->send_mail('subscription_update_failed', $organization->organization->email, $organization->organization->first_name . ' ' . $organization->organization->last_name, $message);

                    $sql = "SELECT * from `sam_admin` WHERE `status` = 'active'";
                    $super_admins = $this->mdl->get_raw_array(null, $sql);

                    // Get the email template, email id = 39
                    $email = "SELECT * FROM sam_table_email_templates WHERE slug_name = 'subscription_update_failed_admin'";
                    $emailtemplate = $this->mdl->get_raw('sam_table_email_templates', $email);
                    $subject = $emailtemplate[0]->subject;
                    $content = $emailtemplate[0]->content;

                    foreach($super_admins as $admin)
                    {
                        $_html_s = file_get_contents(APPPATH . '/libraries/email/common_email_template.html');
                        $_html_s = str_replace("{content}", $content, $_html_s);
                        $_html_s = str_replace("{first_name}", $admin['name'], $_html_s);
                        $_html_s = str_replace("{last_name}", $admin['lastname'], $_html_s);
                        $_html_s = str_replace("{owner_user_email}", $organization->organization->email, $_html_s);
                        $_html_s = str_replace("{mail_logo}", MAIL_LOGO, $_html_s); 
                        $this->sam->send_email('CorpCarbon',$admin['email_id'],$subject,$_html_s,'','',$filePath,$attachment_file);
                    }

                    $plan_status = 'inactive';
                    $this->session->set_userdata('plan_status', $plan_status);
                    $this->session->set_userdata('change_status', '');
                    $this->session->set_flashdata('success', '');
                    $this->session->set_flashdata('error', '');


                }
                else
                {
                    $message = array(
                        'first_name' => $organization->organization->first_name,
                        'last_name' => $organization->organization->last_name,
                        'previous_plan' => $previous_plan,
                        'previous_payment_method' => ucfirst($previous_payment_method),
                        'new_plan' => $new_plan,
                        'payment_method' => $payment_method
                    );
        
                    //echo "<pre>"; print_r($organization->organization);print_r($newOrgData->organization);print_r($message);die;
                    //call function send mail in libraries\Sam.php, pass slug name to find the email template (email id = 21)
                    $this->sam->send_mail('subscription_plan_changed', $organization->organization->email, $organization->organization->first_name . ' ' . $organization->organization->last_name, $message);
        
                    $plan_status = 'active';
                    $this->session->set_userdata('plan_status', $plan_status);
                    $this->session->set_userdata('change_status', 'activate');  
                    $this->session->set_flashdata('success', 'H! '. $organization->organization->first_name.' '. $organization->organization->last_name.' <br> Your subscription plan has been successfully changed!');
                    $this->session->set_flashdata('error', '');
                }
            }

            redirect('subscription');
        }

        $this->data['proration'] = $proration;
        $this->data['organization'] = $organization;
        $this->data['paymentMethods'] = $paymentMethods;
        $this->data['proration_date'] = $proration_date;
        $this->data['selected_plan'] = $selected_plan;
        $meta['page_title'] = lang('Update Subscription');
        $meta['menu'] = 'subscription';
        $this->session->set_flashdata('success', '');
        $this->session->set_flashdata('error', '');
        $this->page_construct('settings/update-subscription', $meta, $this->data);
    }

    public function change_subscription_oldmethod() {

        $meta['page_title'] = lang('Change Plan');
        $meta['menu'] = 'subscription';

        $organization_id = $this->session->userdata('userloginid');
        
        // $oldSubscription = \Stripe\Subscription::retrieve('sub_1PpdaaEVlioaj2T4MCnFAo5z');
        // echo "<pre>"; print_r($subscription); die;

        //Get organization details
        $organization = $this->mdl->get('sam_organizations', $organization_id);
        
        // Get organization subscription details
        $subscription_id = $organization->subscription_id;
        $subscription = ORM::for_table('sam_organization_subscriptions')
        ->where('id', $subscription_id)
        ->where('status', 'active')
        ->find_one();

        // Convert the ORM object to an array
        $subscription_array = $subscription ? $subscription->as_array() : [];

        // Store the array in the data array
        $this->data['activesubscription'] = $subscription_array;  

        $this->data['stripe_customer_id'] = $organization->stripe_customer_id;

        $stripe_plan_id = $subscription->stripe_plan_id;
        $this->data['old_stripe_plan_id'] = $stripe_plan_id; //Need to open once done with testing 

        $stripe_subscription_id = $subscription->stripe_subscription_id;
        $this->data['old_subscription_id'] = $stripe_subscription_id; //Need to open once done with testing 

        //Get organization current plan
        $plan_id = $subscription->plan_id;
        $this->data['plan'] = $this->mdl->get('sam_plans', $plan_id);

        //Get current plan amount
        $current_plan = ORM::for_table('sam_plans')->where('id', $subscription->plan_id)->find_one();
        $this->data['current_plan_amount'] = $current_plan ? $current_plan->amount : 0;

        //Get all active plans
        $_sql = "SELECT p.*, c.symbol FROM sam_plans as p
        JOIN sam_currencies as c ON c.id = p.currency_id
        WHERE status='1'";       
        $this->data['active_plans'] = $this->mdl->get_raw($this->_thisTable, $_sql);

        if($_POST){

            $this->load->database();
            $customer_id = $this->input->post('stripe_customer_id');
            $old_subscription_id = $this->input->post('old_subscription_id');
            $old_stripe_plan_id = $this->input->post('old_stripe_plan_id');
            $new_stripe_plan_id = $this->input->post('new_stripe_plan_id');
            
            $this->load->library('stripe');
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

            if($customer_id != ""){

                $oldSubscription = \Stripe\Subscription::retrieve($old_subscription_id);
                $stripe_subscription_item_id = $oldSubscription->items['data'][0]->id;

                $subscription = \Stripe\Subscription::update(
                    $old_subscription_id,
                    array(
                        'items' => [
                            [
                                'id' => $stripe_subscription_item_id,
                                'price' => $new_stripe_plan_id,
                            ],
                        ],
                    )           
                );
                
                if($subscription){
                    
                    // subscription info 
                    $subscrID   = $subscription['id']; 
                    $custID     = $subscription['customer']; 
                    $planID     = $subscription['plan']['id']; 
                    $planAmount = ($subscription['plan']['amount']/100); 
                    $planCurrency = $subscription['plan']['currency']; 
                    $planInterval = $subscription['plan']['interval']; 
                    $planIntervalCount = $subscription['plan']['interval_count']; 
                    $created = date("Y-m-d H:i:s", $subscription['created']); 
                    $current_period_start = date("Y-m-d H:i:s", $subscription['current_period_start']); 
                    $current_period_end = date("Y-m-d H:i:s", $subscription['current_period_end']); 
                    $status = 'active';//$subscription['status']; 

                    $plan = ORM::for_table('sam_plans')->where('plan_id', $new_stripe_plan_id)->find_one();

                    //Change Previous Subscription status
                    $inactiveSubscription = array('status' => 'inactive'); 
                    $this->db->where('stripe_subscription_id', $old_subscription_id)->update('sam_organization_subscriptions', $inactiveSubscription);

                    // Insert tansaction data into the database 
                    $subscripData = array( 
                        'organization_id' => $organization_id, 
                        'plan_id' => $plan->id, 
                        'stripe_subscription_id' => $subscrID, 
                        'stripe_plan_id' => $planID, 
                        'plan_amount' => $planAmount, 
                        'plan_amount_currency' => $planCurrency, 
                        'plan_interval' => $planInterval, 
                        'plan_interval_count' => $planIntervalCount, 
                        'plan_period_start' => $current_period_start, 
                        'plan_period_end' => $current_period_end, 
                        'payer_email' => $organization->email, 
                        'created_at' => $created, 
                        'status' => $status,
                        'data'   => json_encode($subscription),
                    ); 
                    
                    $this->db->insert('sam_organization_subscriptions', $subscripData);
                    $subscription_id = $this->db->insert_id();

                    // Update subscription id in the organizations table  
                    if($subscription_id && !empty($organization_id)) {
                        $updateData = array('subscription_id' => $subscription_id, 'stripe_customer_id' => $custID); 
                        $this->db->where('id', $organization_id)->update('sam_organizations', $updateData);
                    }
                    
                    $this->session->set_flashdata('success_message', 'Plan updated successfully!');
                    redirect('/org/subscription');
                    
                    //echo "success";
                    exit();
                    
                } else {
                    
                    $this->session->set_flashdata('error', $this->session->flashdata('Something went wrong!'));
                    redirect('/org/change-subscription');
                    
                    //echo 'fail';
                    exit();
                }

            } else {

                $this->session->set_flashdata('error_message', $this->session->flashdata('Customer id not matched!'));
                redirect('/org/change-subscription');
                
                exit();
            }

        }
        
        $this->page_construct('settings/change-subscription', $meta, $this->data);
    }

    public function createSubscription($customerID, $planID, $coupon_code) { 

        $this->load->library('stripe');
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        $subscription = \Stripe\Subscription::create(array( 
            "customer" => $customerID, 
            "items" => array( 
                array( 
                    "plan" => $planID 
                ), 
            ),                     
        ));

        // Retrieve charge details 
        $subsData = $subscription->jsonSerialize(); 
        return $subsData; 
    }

    public function link_payment_success()
    {
        $this->data['message'] = "Your payment has been processed successfully.<br> Thank you!";
        $this->data['title'] = 'Payment Success';
        $this->load->view($this->theme . 'settings/link_payment_message', $this->data);
    }

    public function link_payment_cancel()
    {
        $this->data['message'] = "Your payment has been cancelled.<br> Please try again!";
        $this->data['title'] = 'Payment Cancel';
        $this->load->view($this->theme . 'settings/link_payment_message', $this->data);
    }

    public function downloadFile($file = NULL)
    {
        if ($file)
        {
            $filePath = './uploads/subscriptions/' . $file;

            if (file_exists($filePath))
            {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $file . '"');
                header('Content-Length: ' . filesize($filePath));

                readfile($filePath);
                
                exit;
            }
            else
            {
                echo "File not found";
            }
            
        }
        else
        {
            echo "Document ID not provided";
        }
    }
}