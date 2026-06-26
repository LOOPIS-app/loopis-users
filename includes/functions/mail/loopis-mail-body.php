<?php
/**
 * Standard mail body content template.
 * 
 * @param string $mail_intro The introductory text for the mail
* @param string $mail_content The content of the post 
* @param string $mail_outro The concluding text for the mail
 * @return string HTML wrapped content
 */

if (!defined('ABSPATH')) {
    exit;
}

function get_loopis_mail_body(string $intro, string $content, string $outro): string {
    return '<p>' . $intro . '</p>
    <p style="padding: 10px;font-size: 18px;font-style: italic;background: #f5f5f5;border-radius: 10px">' . $content . '</p>
    <p>' . $outro . '</p>';
}