<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Email Configuration
| -------------------------------------------------------------------------
| Configure your email settings here
| 
| For Gmail SMTP:
| - Go to Google Account settings → Security
| - Enable 2-Step Verification
| - Generate "App Password" for Mail
| - Use the generated 16-character password below
| 
| For other providers (Yahoo, Outlook, etc.), adjust settings accordingly
| 
| IMPORTANT: Update these settings with your actual credentials!
*/

$config['protocol']     = 'smtp';
$config['smtp_host']    = 'smtp.gmail.com';  // Gmail: smtp.gmail.com | Yahoo: smtp.mail.yahoo.com | Outlook: smtp-mail.outlook.com
$config['smtp_port']    = 587;               // 587 for TLS, 465 for SSL
$config['smtp_timeout'] = 30;
$config['smtp_user']    = 'your-company-email@gmail.com';  // CHANGE THIS: Your actual email address
$config['smtp_pass']    = 'your-app-password';     // CHANGE THIS: Your app password (16 characters, no spaces)
$config['smtp_crypto']  = 'tls';                   // 'tls' or 'ssl'
$config['mailtype']     = 'html';
$config['charset']      = 'utf-8';
$config['newline']      = "\r\n";
$config['crlf']         = "\r\n";
$config['wordwrap']     = TRUE;
$config['validate']     = TRUE;

// Sender information (appears in customer's inbox as "FROM")
// This is YOUR business email - stays the same for all bookings
// The customer's email (TO) is taken from the booking form dynamically
$config['sender_email'] = 'your-company-email@gmail.com';  // CHANGE THIS: Same as smtp_user
$config['sender_name']  = 'UCM Venue Booking';     // Your business name
