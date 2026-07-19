<?php

return [
    'validation_failed' => 'Validation failed',
    'unauthenticated' => 'Unauthenticated user',
    // Auth
    'current_password_incorrect' => 'The current password is incorrect',
    'password_changed' => 'Password changed successfully',
    'owner_name_required' => 'The owner name is required',
    'owner_email_required' => 'The owner email is required',
    'owner_email_taken' => 'This owner email is already in use',
    'login_success' => 'Login successful',
    'logout_success' => 'Logged out successfully',
    'invalid_credentials' => 'The provided credentials are incorrect',

    'email_already_verified' => 'Your email is already verified.',
    'verification_link_sent' => 'A verification link has been sent to your email.',

    'expired_otp'           => 'OTP has expired.',
    'password_reset_success' => 'Password has been successfully reset.',
    'register_success' => 'User registered successfully.',
    'otp_sent' => 'OTP sent successfully.',
    'otp_wait' => 'OTP already sent. Please wait :seconds seconds before requesting another.',
    'invalid_otp' => 'Invalid OTP.',
    'otp_expired' => 'OTP expired.',
    'otp_verified' => 'OTP verified successfully.',
    'profile_retrieved' => 'User profile retrieved successfully.',


    'profile_updated' => 'User profile updated successfully.',
    'statistics_retrieved' => 'Statistics retrieved successfully.',
    'data_retrieved' => 'data retrieved successfully.',
    'data_saved' => 'Data saved successfully',
    'data_deleted' => 'Data deleted successfully',
    'data_updated' => 'Data updated successfully',

    'user_required' => 'User is required',
    'user_not_found' => 'Selected user must be a valid owner',
    'company_not_found' => 'No company is linked to your account yet.',

    'email_already_exists' => 'Email already exists',

    'logo_required' => 'Logo is required',
    'logo_must_be_image' => 'Logo must be an image file',


    'employee_not_found' => 'Employee not found',
    'template_not_found' => 'Template not found',
    'department_not_found' => 'Department not found',
    'employee_company_forbidden' => 'You can only manage employees of your own company.',
    'company_scope_forbidden' => 'You can only manage records that belong to your own company.',

    // Notification titles/bodies (business-card lifecycle).
    'notif_card_submitted_title' => 'Card ready for your approval',
    'notif_card_submitted_body'  => 'A digital business card has been created for you. Open the app to review and approve it.',
    'notif_card_approved_title'  => 'Card approved',
    'notif_card_approved_body'   => ':name approved their business card.',
    'notif_card_rejected_title'  => 'Card rejected',
    'notif_card_rejected_body'   => ':name rejected their business card: :reason',
    'notif_card_published_title' => 'Your card is live',
    'notif_card_published_body'  => 'Your digital business card has been published and can now be shared.',
    'employee_user_invalid' => 'This user cannot be linked to an employee.',

    'employee_required' => 'Employee is required',
    'template_required' => 'Template is required',

    'business_card_submitted' => 'Business card submitted successfully',
    'business_card_approved'  => 'Business card approved successfully',
    'business_card_rejected'  => 'Business card rejected successfully',
    'business_card_published' => 'Business card published successfully',
    'business_card_deactivated' => 'Business card deactivated successfully',

    'business_card_must_be_approved' => 'Business card must be approved before publishing',

    'employee_business_card_exists' => 'Employee already has a business card',

    'business_card_unavailable' => 'This business card is no longer available.',

    // --- Employee card personalisation + owner review -----------------------
    'no_card_yet' => 'You do not have a business card yet. Your company will create one for you.',
    'card_locked_for_editing' => 'This card is waiting for review or already published, so it cannot be edited right now.',
    'card_already_submitted' => 'This card has already been sent for review.',
    'card_not_awaiting_review' => 'This card is not waiting for review.',
    'color_must_be_hex' => 'Colours must be a hex value like #22D3EE.',
    'business_card_changes_requested' => 'Changes requested successfully',

    'notif_card_review_title' => 'A card needs your review',
    'notif_card_review_body'  => ':name personalised their business card and sent it for your review.',
    'notif_card_changes_title' => 'Changes requested on your card',
    'notif_card_changes_body'  => 'Your company asked for a change: :comment',
    'notif_card_owner_approved_title' => 'Your card was approved',
    'notif_card_owner_approved_body'  => 'Your company approved your business card. It will be published shortly.',

    'cannot_approve_own_card' => 'You cannot approve your own card. Your company reviews it for you.',

    'password_expired' => 'Your password has expired. Please reset your password to continue.',
];
