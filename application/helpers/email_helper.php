<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Send booking confirmation email
 * 
 * @param array $booking_data - Contains all booking information
 * @return bool - Success or failure
 */
if (!function_exists('send_booking_confirmation')) {
    function send_booking_confirmation($booking_data) {
        $CI =& get_instance();
        
        // Load email library and config
        $CI->load->library('email');
        $CI->config->load('email');
        
        // Get email configuration
        $sender_email = $CI->config->item('sender_email');
        $sender_name = $CI->config->item('sender_name');
        
        // Prepare email content
        $to_email = $booking_data['customer_email'];
        $subject = 'Booking Confirmation - Invoice #' . $booking_data['invoice_number'];
        
        // Generate email body
        $message = generate_booking_email_template($booking_data);
        
        // Set email parameters
        $CI->email->from($sender_email, $sender_name);
        $CI->email->to($to_email);
        $CI->email->subject($subject);
        $CI->email->message($message);
        
        // Send email
        if ($CI->email->send()) {
            return true;
        } else {
            log_message('error', 'Email sending failed: ' . $CI->email->print_debugger());
            return false;
        }
    }
}

/**
 * Generate HTML email template for booking confirmation
 * 
 * @param array $booking_data
 * @return string - HTML email content
 */
if (!function_exists('generate_booking_email_template')) {
    function generate_booking_email_template($booking_data) {
        $CI =& get_instance();
        
        // Load helper for date formatting
        $CI->load->helper('tgl_indo');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3498db; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .booking-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .detail-row { padding: 8px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: bold; display: inline-block; width: 150px; }
        .detail-value { color: #555; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table th { background: #3498db; color: white; padding: 10px; text-align: left; }
        .items-table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .total-section { background: #fff3cd; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .total-row { padding: 5px 0; font-size: 16px; }
        .grand-total { font-size: 20px; font-weight: bold; color: #27ae60; }
        .payment-info { background: #d1ecf1; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #0c5460; }
        .footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }
        .btn-primary { background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-unpaid { background: #ffc107; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Booking Confirmation</h1>
            <p>Thank you for your booking!</p>
        </div>
        
        <div class="content">
            <h2>Hello ' . htmlspecialchars($booking_data['customer_name']) . ',</h2>
            <p>Your booking has been successfully confirmed. Here are your booking details:</p>
            
            <div class="booking-details">
                <h3>Booking Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Invoice Number:</span>
                    <span class="detail-value"><strong>' . htmlspecialchars($booking_data['invoice_number']) . '</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Booking Date:</span>
                    <span class="detail-value">' . tgl_indo($booking_data['booking_date']) . '</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-unpaid">UNPAID - AWAITING PAYMENT</span>
                </div>
            </div>
            
            <h3>Booking Details</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Venue</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>';
        
        // Loop through booking items
        foreach ($booking_data['items'] as $item) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($item['venue_name']) . '</td>
                        <td>' . date('d M Y', strtotime($item['date'])) . '</td>
                        <td>' . $item['start_time'] . ' - ' . $item['end_time'] . '</td>
                        <td>' . $item['duration'] . ' hours</td>
                        <td>Rp ' . number_format($item['total']) . '</td>
                    </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span style="float: right;">Rp ' . number_format($booking_data['subtotal']) . '</span>
                </div>
                <div class="total-row">
                    <span>Discount:</span>
                    <span style="float: right;">Rp ' . number_format($booking_data['discount']) . '</span>
                </div>
                <hr>
                <div class="total-row grand-total">
                    <span>Grand Total:</span>
                    <span style="float: right;">Rp ' . number_format($booking_data['grand_total']) . '</span>
                </div>
            </div>';
        
        // Add notes if exists
        if (!empty($booking_data['notes'])) {
            $html .= '<div class="booking-details">
                        <strong>Notes:</strong><br>
                        ' . nl2br(htmlspecialchars($booking_data['notes'])) . '
                    </div>';
        }
        
        $html .= '<div class="payment-info">
                <h3>⏰ Payment Information</h3>
                <p><strong>Payment Deadline:</strong> ' . date('d M Y H:i', strtotime($booking_data['deadline'])) . ' WIB</p>
                <p><strong>Amount to Pay:</strong> <span style="font-size: 18px; color: #27ae60;"><strong>Rp ' . number_format($booking_data['grand_total']) . '</strong></span></p>
                <p>Please complete your payment before the deadline. Your booking will be automatically cancelled if payment is not received.</p>
            </div>
            
            <div style="text-align: center;">
                <a href="' . base_url('cart/history') . '" class="btn-primary">View Booking Details</a>
            </div>
            
            <p><strong>Bank Transfer Details:</strong></p>
            <ul>';
        
        // Add bank details
        if (!empty($booking_data['banks'])) {
            foreach ($booking_data['banks'] as $bank) {
                $html .= '<li><strong>' . $bank->nama_bank . ':</strong> ' . $bank->norek . ' (a.n. ' . $bank->atas_nama . ')</li>';
            }
        }
        
        $html .= '</ul>
            
            <p>After payment, please confirm your payment through our website or contact our customer service.</p>
            
            <p style="margin-top: 30px;">If you have any questions, please don\'t hesitate to contact us.</p>
            
            <p>Best regards,<br><strong>UCM Venue Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; ' . date('Y') . ' UCM Venue. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}
