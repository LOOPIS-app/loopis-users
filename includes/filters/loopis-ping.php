<?php
/**
 * Replace comment mentions with LOOPIS ping.
 * 
 * @package LOOPIS_Users
 * @subpackage Ping
 */


add_action('rest_api_init', function () {
    register_rest_route('loopis-users/v1', '/user-logins', [
        'methods'  => WP_REST_Server::READABLE,

        'permission_callback' => function () {
            return is_user_logged_in();
        },

        'callback' => function () {
            $users = get_users([
                'blog_id' => get_current_blog_id(),
                'exclude' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                'fields' => ['ID', 'user_login', 'display_name'],
                'orderby' => 'user_login',
                'order' => 'ASC',
            ]);

            $result = array_map(function ($user) {
                return [
                    'id'    => (int) $user->ID,
                    'login' => $user->user_login,
                    'display_name' => $user->display_name,
                ];
            }, $users);
            array_unshift($result, [
                'id'    => 0,
                'login' => 'admin',
                'display_name' => 'Admin',
            ]);

            return rest_ensure_response($result);
        },
    ]);
});

add_action('wp_enqueue_scripts', function () {
    if (!is_singular() || !comments_open()) {
        return;
    }
    wp_register_script(
        'loopis-users-mentions',
        LOOPIS_USERS_URL . '/assets/js/ping.js',
        [],
        LOOPIS_USERS_VERSION,
        true
    );

    wp_localize_script('loopis-users-mentions', 'loopisUserData', [
        'restUrl' => esc_url_raw(rest_url('loopis-users/v1/user-logins')),
        'nonce'   => wp_create_nonce('wp_rest'),
    ]);

    wp_enqueue_script('loopis-users-mentions');
});
  



function loopis_mention_mail(string $comment_content, int $comment_id, int $sender_id, int $recipient_id){
    loopis_get_mail_templates();

    $sender = get_userdata($sender_id);
    $sender_name = $sender ? $sender->display_name : '';

    $recipient = get_userdata($recipient_id);
    $to = $recipient ? $recipient->user_email : '';

    $comment = get_comment($comment_id);
    if (!$comment) {
        return;
    }

    $post_title = get_the_title($comment->comment_post_ID);
    $post_link = get_permalink( $comment->comment_post_ID );

    $comment_content = preg_replace('/\t+/', '', $comment_content); // Remove all tabs
    $comment_content = preg_replace('/\n\s+/', "\n", $comment_content); // Remove leading spaces on new lines
    $subject = "🔔 {$post_title}";
    $headers = loopis_mail_headers();
    $outro = array(loopis_mail_ping($sender_name, $post_link, $post_title));

    $the_email = loopis_mail_template('',$outro, $comment_content,'Du är omnämnd i en kommentar:');
    $the_email .= loopis_mail_footer('Gå till LOOPIS.app för att hantera annonsen eller skriva ett svar.');


    wp_mail($to, $subject, $the_email, $headers); 
}




add_action('comment_post', 'loopis_process_comment_mentions', 10, 3);

function loopis_process_comment_mentions($comment_id, $comment_approved, $comment_data) {
    if ((string) $comment_approved !== '1') {
        return;
    }

    $content = $comment_data['comment_content'] ?? '';

    preg_match_all(
        '/(?<![A-Za-z0-9_.-])@([A-Za-z0-9_.-]+)/',
        $content,
        $matches
    );

    if (empty($matches[1])) {
        return;
    }

    $users = [];

    foreach (array_unique($matches[1]) as $login) {

        if (strtolower($login) === 'admin'){
            $manager_users = get_users(array(
		        'role__in' => ['manager'],
                'blog_id'  => get_current_blog_id(),
		        'fields' => 'all'
	        ));    
            foreach ($manager_users as $user) {
                if ($user instanceof WP_User) {
                    $users[$user->ID] = $user;
                }
            }
            
        }else{
            $user = get_user_by('login', $login);
            if ($user instanceof WP_User) {
                $users[$user->ID] = $user;
            }
        }
    }

    if (!$users) {
        return;
    }

    $user_ids = array_map('intval', array_keys($users));

    $comment = get_comment($comment_id);
    if (!$comment) {
        return;
    }

    foreach ($users as $user) {
        if ((int) $user->ID === (int) $comment->user_id) {
            continue;
        }
        loopis_mention_mail($content, $comment_id, $comment->user_id, $user->ID);
    }
}
