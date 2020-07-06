<?php

use Illuminate\Database\Seeder;

class EmailtemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currentTime = date("Y-m-d H:i:s");
        $templateArr = [
            ['template_name' => 'success_lead_sent','subject' =>'Lead Sent to User','body' => '
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">You have successfully accepted <a style="color:#3a91cd" href="%ACTIVATION_LINK%">this</a> lead. Your contact information has been shared with the user and you can now contact the user directly by visiting the link above.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST. </p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>
                Palm Beach Gardens, FL 33410</p>
                ', 'status' => '1','created_at' => $currentTime,'updated_at' => $currentTime
            ],

            ['template_name' => 'business_registration_activation','subject' => 'Activate Your Account','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome aboard %NAME%, </span>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Please click <a style="color:#3a91cd" href="%ACTIVATION_LINK%">here</a> to activate account!</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                    <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                    <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600
                    <br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],

            ['template_name' => 'registration_activation','subject' => 'Registration activation','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome aboard %FIRSTNAME% %LASTNAME%,</span>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Please click <a style="color:#3a91cd" href="%ACTIVATION_LINK%">here</a> to activate account!</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                    <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                    <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                    ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],

            ['template_name' => 'claimed_business_notification','subject' => 'Claimed Business Verification','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Your request to claim this business is being reviewed by our team. We will notify you within two business days of your approval status.</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                    <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                    <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                    ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],

             ['template_name' => 'forget_password','subject' => 'Reset Password','body' => '<p style="font-size: 17px;line-height: 22px;margin-top: auto;">We have received a request to reset your password for your account. If you want to reset your password, click <a style="color:#3a91cd" href="%ACTIVATION_LINK%" target="_blank">here.</a> If you did not request to reset your password, then please ignore this message.</p> 
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can email us at <a style="color:#3a91cd" href="mailto:info@marinecentral.com">info@marinecentral.com</a> or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST. </p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>
                  Palm Beach Gardens, FL 33410</p>','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'resend_confirmation','subject' => 'Resend Confirmation','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome aboard %NAME%,</span>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Please click <a style="color:#3a91cd" href="%ACTIVATION_LINK%">here</a> to activate your account!</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'approve_claimbusiness','subject' => 'Business Claim Approval for %NAME%','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome aboard %NAME%,</span>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">You have successfully claimed your business! Please click <a style="color:#3a91cd" href="%ACTIVATION_LINK%">here</a> to activate your account.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'approve_claimbusiness_social','subject' => 'Approval for %NAME%','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">We have received your claimed request and it has been approved. Please <a style="color:#3a91cd" href="%ACTIVATION_LINK%"> click here</a> to login you account.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'reject_claimbusiness','subject' => 'Claimed Business Denied','body' => '
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Your request to claim %NAME%, has been denied. If a payment was received, we will issue a refund within 7 business days. If you feel there was error with this request, please contact us directly by replying to this email or give us a call at 561-478-0812 to resubmit your request with the correct credentials.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'lead_notification','subject' => 'New Lead','body' => '
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">A user has added a new Service Request on Marine Central. Please click <a style="color:#3a91cd" href="%SERVICE_REQUEST_LINK%">here</a> to view this request.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'business_added_by_admin','subject' => 'Account Created','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome Aboard %NAME%.</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Your account was created successfully by the Administrator. Please check below for your credentials. You may now login. <a style="color:#3a91cd" href="https://www.marinecentral.com/login">link</a></p>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Email - %EMAIL%</p> 
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Password - %PASSWORD%</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'user_added_by_admin','subject' => 'Account Created','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome Aboard %FIRSTNAME% %LASTNAME%</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Your account was created successfully by the Administrator. Please check below for your credentials. You may now login. <a style="color:#3a91cd" href="https://www.marinecentral.com/login">link</a></p> 
                    <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Email - %EMAIL%</p> 
                    <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Password - %PASSWORD%</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'user_deleted','subject' => 'Account deleted','body' => '
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Your account has been deleted by the Administrator. Please contact us at info@marinecentral.com for any inquiries you may have. <br>Thank you for using Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'job_notification','subject' => 'New Job Posted','body' => '
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">%NAME% has posted a new job opening. Please click <a style="color:#3a91cd" href="%JOB_DETAIL_LINK%">here</a> to view the listing.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],

            ['template_name' => 'bad_rating_notification','subject' => 'Bad rating','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Your business has received a bad review <a style="color:#3a91cd" href="%LINK%">%LINK%</a>. It happens and we are here to help! We strive to help businesses succeed, so we will give you 48 hours to rectify this review before it is published. If you are unable to resolve the issues with the customer, we recommend replying directly to the bad review to show others you are attempting to resolve any issues immediately and have every intention on delivering ceptional customer service.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
            ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'email_change_notification','subject' => 'Email address changed','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>

                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Your email address has been changed. <br>To confirm this new email, follow the link sent to %EMAIL%. If you did not make this change, please contact Marine Support immediately.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'email_change_confirmation','subject' => 'Email Address Change Requested','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">
                We’ve received your request to update your email address. To complete your update, you will need to confirm your email address using the link below.
                <br><a style="color:#3a91cd" href="%LINK%"> %LINK%</a><br>    
                If you did not make this change, please contact support immediately
                </p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            
            ['template_name' => 'reject_lead_notification','subject' => 'Lead Cancelled','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">
                The Service Request for %TITLE% has been cancelled or closed by the user.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'approved_lead_notification','subject' => 'Lead Approve','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">The Service Request for %TITLE% has been approved by the user.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'request_quotes_notification','subject' => 'Request Quote','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">A user has requested a quote from your business profile page. Please click <a style="color:#3a91cd" href="%MESSAGE_LINK%">here</a> to respond.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'new_lead_request','subject' => 'Lead Request','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">%BUSINESS_NAME% send a lead for service request. Click <a style="color:#3a91cd" href="%SERVICE_LINK%">here</a> to view service request details.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'job_applied','subject' => 'New Applicant','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Good News! A candidate has applied to your job post. Please click <a style="color:#3a91cd" href="%JOB_LINK%">here</a> to review.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'subscription_reminder','subject' => 'Reminder: Your Membership Plan will Expire in %DAY%','body' => '
				<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%.</span>
				<p style="font-size: 17px;line-height: 24px;margin-top: auto;">Your Marine Central Membership Plan will expire on %DATE%. Please renew your membership plan prior to the expiration date to avoid interruption with your account. To access your account please click this <a style="color:#3a91cd" href="%LINK%"> %LINK%</a>.</p>
				<p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
				<p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
				<p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
				<p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
				','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
			],
            ['template_name' => 'admin_emailchange_notification','subject' => 'Your Email Address Has Been Changed by the Administrator','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Your email address has been changed by the administrator</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">Thank you for using Marine Central</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'admin_emailchange_notification_new','subject' => 'Your Email Address Has Been Changed by the Administrator','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Please use this email address on the link provided to login.</p>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;"><a href="https://www.marinecentral.com/login">link</a></p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">Thank you for using Marine Central</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'admin_passwordchange_notification','subject' => 'Your Password Has Been Changed by the Administrator','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Your password has been changed by the administrator. Your new password to access account is:
                </p>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Password - %PASSWORD%</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">Thank you for using Marine Central</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'admin_emailPwdchange_notification','subject' => 'Your Email Address and Password Has Been Changed by the Administrator','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Your account has been updated by the administrator.</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">Thank you for using Marine Central</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'admin_emailPwdchange_notification_new','subject' => 'Your Email Address and Password Has Been Changed by the Administrator','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Please use this email address and password on the link provided to login.</p>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Password: %PASSWORD%</p>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;"><a href="https://www.marinecentral.com/login">link</a></p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">Thank you for using Marine Central</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'unreadMessage_reminder','subject' => 'You have a new message on Marine Central','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%.</span>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">You have a new message from %FROM_NAME%. Please click <a style="color:#3a91cd" href="%LINK%">here</a> to view the message.</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'import_failed','subject' => 'Import Failed','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%.</span>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">The requested import failed as a result of dulicate email. List of records failed to import:- %FAILED_EMAIL%</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'import_success','subject' => 'Records Imported Successfully','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%.</span>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">All requested records from the CSV file have been successfully imported.</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'import_success_failed','subject' => 'Records Imported Successfully','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%.</span>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Requested records from the CSV file have been successfully imported. Some records was failed to import due to duplicate email. List of records failed to import:- %FAILED_EMAIL%</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'business_registration_activation_discount','subject' => 'Activate Your Account','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome aboard %NAME%,</span>
					<p style="font-size: 17px;line-height: 24px;margin-top: auto;">Your credential to access account is <br><b>Email - %EMAIL%</b><br><b>Password - %PASSWORD%</b></p>
					<p style="font-size: 17px;line-height: 24px;margin-top: auto;">Please click <a style="color:#3a91cd" href="%ACTIVATION_LINK%">here</a> to activate your account!</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                    <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                    <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],
            ['template_name' => 'website_rating_notification','subject' => 'Website rating','body' => '
                <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello admin,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">There is a new rating done for the website and below are the details for it</p>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">Username - %USERNAME% <br>
                Profilelink - %LINK% <br>
                Comment - %COMMENT% <br>
                Rating - %RATE%/5
                </p> 
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'user_registration_and_service_request','subject' => 'Service Request','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome aboard %NAME%,</span>
					<p style="font-size: 17px;line-height: 24px;margin-top: auto;">A new service request has been created using this email address, Please click <a style="color:#3a91cd" href="%ACTIVATION_LINK%">here</a> to confirm. Also we have created an account based on information you provided and below are credential for same <br><b>Email - %EMAIL%</b><br><b>Password - %PASSWORD%</b></p>
					<p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                    <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                    <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],
           
            ['template_name' => 'user_registration_and_request_quote','subject' => 'Request Quote','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Welcome aboard %NAME%,</span>
					<p style="font-size: 17px;line-height: 24px;margin-top: auto;">A request quote has been created using this email address, Please click <a style="color:#3a91cd" href="%ACTIVATION_LINK%">here</a> to confirm. Also we have created an account based on information you provided and below are credential for same <br><b>Email - %EMAIL%</b><br><b>Password - %PASSWORD%</b></p>
					<p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                    <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                    <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],
             ['template_name' => 'contact_us_notification_admin','subject' => 'Contact Us','body' => '<span style="padding-bottom: 10px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 24px;">Hello Admin,</span>  <p  style="font-size: 17px;line-height: 24px;margin-top: auto;">There is a new message from Marine Central</p><p><br><b>Name - %NAME%</b><br><b>Email - %EMAIL%</b><br><b>Contact number - %CONTACT%</b><br><b>Message-</b>%MESSAGE%</p>','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],
             ['template_name' => 'resend_email_otp','subject' => 'Email Changed OTP','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello %NAME%,</span>
                <p style="font-size: 17px;line-height: 22px;margin-top: auto;">
                Confirm your new email using OTP <b>%OTP%</b>.
                <br>    
                If you did not make this change, please contact support immediately
                </p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">If you have any questions or need assistance, you can reply directly to this email or give us a call at 561-472-0812. Our office hours are Monday – Friday from 8am – 5pm EST.</p>
                <p style="font-size: 22px;line-height: 24px;margin-top: 30px;font-weight:  bold;margin-bottom: 6px;">Marine Central</p>
                <p style="font-size: 17px;line-height: 24px;margin-top: auto;">Connecting the marine industry one lead at a time!</p>
                <p style="font-size: 17px;line-height: 23px;margin-top: auto;">4440 PGA BLVD Suite 600<br>Palm Beach Gardens, FL 33410</p>
                ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime 
            ],
            ['template_name' => 'admin_new_user_notification','subject' => 'New User Registered','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello Admin,</span>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">A new %TYPE% is  registered on website.</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">NAME:- %NAME%<br>Email:- %EMAIL%<br></p>
                    ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],

            ['template_name' => 'admin_new_service_notification','subject' => 'New Service Request Added','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello Admin,</span>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">A new service request has been added by %NAME%. Click here <a style="color:#3a91cd" href="%LINK%"> %LINK%</a> for more detail.</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">NAME:- %NAME%<br>Email:- %EMAIL%<br></p>
                    ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ],
            ['template_name' => 'admin_new_user_new_service_notification','subject' => 'New Service Request Added','body' => '<span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #3a91cd;text-transform: uppercase; line-height: 26px;">Hello Admin,</span>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">A new %TYPE% is registered and added new service request. Click here <a style="color:#3a91cd" href="%LINK%"> %LINK%</a> for more detail.</p>
                    <p style="font-size: 17px;line-height: 24px;margin-top: auto;">NAME:- %NAME%<br>Email:- %EMAIL%<br></p>
                    ','status' => '1','created_at' => $currentTime,'updated_at' => $currentTime ]
           
   
        ];    
        DB::table('email_templates')->delete();
        foreach($templateArr as $template){
            DB::table('email_templates')->insert($template);
        }
    }
}


// resend confirmation paraagraph style style="font-size: 1em; line-height: 1.4;"

// newe posted job -> <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #fff;text-transform: uppercase;"></span>

// lead_notification -> <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #fff;text-transform: uppercase;">%FIRSTNAME% %LASTNAME% has added a new service request.</span>

// success_lead_sent -> <span style="padding-bottom: 15px;display:block;font-size: 20px;font-weight: bold;color: #fff;text-transform: uppercase;">Hello user!</span>
