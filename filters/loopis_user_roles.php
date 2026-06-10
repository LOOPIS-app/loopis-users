<?php
/**
 * Enable multiple roles for users.
 * 
 * @package LOOPIS_Users
 * @subpackage User_Roles
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

/**
 * Get editable roles with safe loading in non-admin contexts.
 *
 * @return array
 */
function loopis_get_editable_roles() {
    if ( ! function_exists( 'get_editable_roles' ) ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    return (array) get_editable_roles();
}

/**
 * Determine whether current user can manage role assignment.
 *
 * @param int $target_user_id Optional user ID being edited.
 * @return bool
 */
function loopis_can_manage_roles( $target_user_id = 0 ) {
    if ( current_user_can( 'promote_users' ) ) {
        if ( $target_user_id > 0 ) {
            return current_user_can( 'edit_user', $target_user_id );
        }

        return true;
    }

    if ( is_multisite() && is_super_admin() ) {
        return true;
    }

    return false;
}

/**
 * Render multiple role checkboxes on user profile screens.
 *
 * @param WP_User $user User object.
 * @return void
 */
function loopis_render_user_roles_field( $user ) {
    if ( ! ( $user instanceof WP_User ) ) {
        return;
    }

    if ( ! loopis_can_manage_roles( (int) $user->ID ) ) {
        return;
    }

    $editable_roles = loopis_get_editable_roles();
    $user_roles     = array_map( 'strval', (array) $user->roles );

    wp_nonce_field( 'loopis_save_user_roles', 'loopis_user_roles_nonce' );
    ?>
    <div id="loopis-multi-roles-panel" class="loopis-multi-roles-panel">
        <h2><?php esc_html_e( 'Roles', 'loopis-users' ); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="loopis_roles"><?php esc_html_e( 'Assigned roles', 'loopis-users' ); ?></label></th>
                <td>
                    <fieldset id="loopis_roles">
                        <?php foreach ( $editable_roles as $role_key => $role_data ) : ?>
                            <label style="display:block;margin:0 0 4px;">
                                <input type="checkbox" name="loopis_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $user_roles, true ) ); ?>>
                                <?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

/**
 * Render multiple role checkboxes on create-user screen.
 *
 * @return void
 */
function loopis_render_new_user_roles_field() {
    if ( ! loopis_can_manage_roles() ) {
        return;
    }

    $editable_roles = loopis_get_editable_roles();
    wp_nonce_field( 'loopis_save_user_roles', 'loopis_user_roles_nonce' );
    ?>
    <div id="loopis-multi-roles-panel" class="loopis-multi-roles-panel">
        <h2><?php esc_html_e( 'Roles', 'loopis-users' ); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="loopis_roles"><?php esc_html_e( 'Assigned roles', 'loopis-users' ); ?></label></th>
                <td>
                    <fieldset id="loopis_roles">
                        <?php foreach ( $editable_roles as $role_key => $role_data ) : ?>
                            <label style="display:block;margin:0 0 4px;">
                                <input type="checkbox" name="loopis_roles[]" value="<?php echo esc_attr( $role_key ); ?>">
                                <?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

/**
 * Tweak WP admin user role UI for multi-role editing.
 *
 * @return void
 */
function loopis_user_roles_admin_ui_tweaks() {
    if ( ! loopis_can_manage_roles() ) {
        return;
    }
    ?>
    <script>
    (function() {
        var panel = document.getElementById('loopis-multi-roles-panel');
        if (!panel) {
            return;
        }

        var nativeRoleSelect = document.querySelector('select#role, select[name="role"]');
        if (!nativeRoleSelect) {
            return;
        }

        var nativeRoleRow = nativeRoleSelect.closest('tr');
        if (!nativeRoleRow) {
            return;
        }

        // Hide native single-role row to avoid conflicting assignment UX.
        nativeRoleRow.style.display = 'none';
        nativeRoleSelect.disabled = true;

        // Move custom multi-role block next to where role settings normally live.
        var roleSection = nativeRoleRow.closest('table.form-table');
        if (roleSection && roleSection.parentNode) {
            roleSection.parentNode.insertBefore(panel, roleSection.nextSibling);
        }
    })();
    </script>
    <?php
}

/**
 * Parse requested roles from form post.
 *
 * @return array
 */
function loopis_get_requested_roles_from_post() {
    $editable_roles = array_keys( loopis_get_editable_roles() );
    $requested      = isset( $_POST['loopis_roles'] ) ? (array) $_POST['loopis_roles'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

    $requested = array_map( 'sanitize_key', $requested );
    $requested = array_values( array_intersect( $requested, $editable_roles ) );

    if ( empty( $requested ) && isset( $_POST['role'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $single_role = sanitize_key( wp_unslash( $_POST['role'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( in_array( $single_role, $editable_roles, true ) ) {
            $requested[] = $single_role;
        }
    }

    return array_values( array_unique( $requested ) );
}

/**
 * Save multiple roles for an existing user.
 *
 * @param int $user_id User ID.
 * @return void
 */
function loopis_save_user_roles( $user_id ) {
    if ( ! loopis_can_manage_roles( (int) $user_id ) ) {
        return;
    }

    if ( ! isset( $_POST['loopis_user_roles_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['loopis_user_roles_nonce'] ) ), 'loopis_save_user_roles' ) ) {
        return;
    }

    $user = new WP_User( $user_id );
    if ( ! $user->exists() ) {
        return;
    }

    $requested_roles = loopis_get_requested_roles_from_post();

    if ( empty( $requested_roles ) ) {
        $requested_roles = ! empty( $user->roles ) ? array( (string) $user->roles[0] ) : array( get_option( 'default_role', 'subscriber' ) );
    }

    foreach ( (array) $user->roles as $existing_role ) {
        if ( ! in_array( $existing_role, $requested_roles, true ) ) {
            $user->remove_role( $existing_role );
        }
    }

    foreach ( $requested_roles as $role ) {
        if ( ! in_array( $role, (array) $user->roles, true ) ) {
            $user->add_role( $role );
        }
    }
}

/**
 * Save multiple roles for newly registered user.
 *
 * @param int $user_id User ID.
 * @return void
 */
function loopis_save_new_user_roles( $user_id ) {
    if ( ! loopis_can_manage_roles( (int) $user_id ) ) {
        return;
    }

    if ( ! isset( $_POST['loopis_user_roles_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['loopis_user_roles_nonce'] ) ), 'loopis_save_user_roles' ) ) {
        return;
    }

    $requested_roles = loopis_get_requested_roles_from_post();
    if ( empty( $requested_roles ) ) {
        return;
    }

    $user = new WP_User( $user_id );
    if ( ! $user->exists() ) {
        return;
    }

    foreach ( (array) $user->roles as $existing_role ) {
        $user->remove_role( $existing_role );
    }

    foreach ( $requested_roles as $role ) {
        $user->add_role( $role );
    }
}

add_action( 'show_user_profile', 'loopis_render_user_roles_field' );
add_action( 'edit_user_profile', 'loopis_render_user_roles_field' );
add_action( 'user_new_form', 'loopis_render_new_user_roles_field' );

add_action( 'profile_update', 'loopis_save_user_roles', 100, 1 );
add_action( 'user_register', 'loopis_save_new_user_roles' );

add_action( 'admin_footer-user-edit.php', 'loopis_user_roles_admin_ui_tweaks' );
add_action( 'admin_footer-profile.php', 'loopis_user_roles_admin_ui_tweaks' );
add_action( 'admin_footer-user-new.php', 'loopis_user_roles_admin_ui_tweaks' );

