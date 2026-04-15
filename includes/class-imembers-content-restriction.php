<?php
/**
 * Content Restriction class for iMembers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_Content_Restriction {

    public function init() {
        // Meta box for post/page restriction
        add_action( 'add_meta_boxes', array( $this, 'add_restriction_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_restriction_meta_box' ) );

        // Category/Term restriction fields
        add_action( 'category_add_form_fields', array( $this, 'add_term_restriction_field' ) );
        add_action( 'category_edit_form_fields', array( $this, 'edit_term_restriction_field' ) );
        add_action( 'create_category', array( $this, 'save_term_restriction_field' ) );
        add_action( 'edited_category', array( $this, 'save_term_restriction_field' ) );

        // Shortcode for parsing members_only content
        add_shortcode( 'members_only', array( $this, 'members_only_shortcode' ) );
        add_shortcode( 'imembers_download', array( $this, 'download_shortcode' ) );

        // Template redirect for checking access
        add_action( 'template_redirect', array( $this, 'check_access' ) );

        // Filter content to show teaser for logged-in users with insufficient level
        add_filter( 'the_content', array( $this, 'filter_content_by_level' ) );
    }

    /**
     * Helper to get restriction level for a post/page
     */
    private function get_post_level( $post_id ) {
        $level = get_post_meta( $post_id, '_imembers_restriction_level', true );
        if ( $level === '' ) {
            // Check legacy flag
            if ( get_post_meta( $post_id, '_imembers_is_restricted', true ) === '1' ) {
                return 1;
            }
            return 0;
        }
        return intval( $level );
    }

    /**
     * Helper to get restriction level for a term
     */
    private function get_term_level( $term_id ) {
        $level = get_term_meta( $term_id, '_imembers_restriction_level', true );
        if ( $level === '' ) {
            // Check legacy flag
            if ( get_term_meta( $term_id, '_imembers_is_restricted', true ) === '1' ) {
                return 1;
            }
            return 0;
        }
        return intval( $level );
    }

    /**
     * Helper to check if user meets level requirement
     */
    private function user_meets_level( $required_level ) {
        if ( $required_level <= 0 ) return true;
        if ( ! is_user_logged_in() ) return false;
        
        // Level 1: Any logged in user
        if ( $required_level === 1 ) return true;
        
        // Level 2: Paid subscribers only
        if ( $required_level === 2 ) {
            return get_user_meta( get_current_user_id(), '_imembers_pro_subscriber', true ) === '1';
        }
        
        return false;
    }

    public function add_restriction_meta_box() {
        $screens = array( 'post', 'page' ); // Add to posts and pages
        foreach ( $screens as $screen ) {
            add_meta_box(
                'imembers_restriction_box',
                'iMembers 閲覧制限',
                array( $this, 'render_restriction_meta_box' ),
                $screen,
                'side',
                'high'
            );
        }
    }

    public function render_restriction_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'imembers_restriction_nonce_action', 'imembers_restriction_nonce' );

        $level = get_post_meta( $post->ID, '_imembers_restriction_level', true );
        
        // Backward compatibility: if old checkbox was checked, set to level 1
        if ( $level === '' && get_post_meta( $post->ID, '_imembers_is_restricted', true ) === '1' ) {
            $level = '1';
        }
        
        if ( $level === '' ) $level = '0'; // Default to Public
        ?>
        <p>
            <label for="imembers_restriction_level">閲覧権限:</label><br>
            <select name="imembers_restriction_level" id="imembers_restriction_level" style="width:100%; margin-top:5px;">
                <option value="0" <?php selected( $level, '0' ); ?>>公開 (制限なし)</option>
                <option value="1" <?php selected( $level, '1' ); ?>>会員限定 (無料/有料)</option>
                <option value="2" <?php selected( $level, '2' ); ?>>有料会員限定</option>
            </select>
        </p>
        <p class="description">
            「有料会員限定」はStripe決済済みのユーザーのみ閲覧可能です。
        </p>
        <?php
    }

    public function save_restriction_meta_box( $post_id ) {
        // Check nonce
        if ( ! isset( $_POST['imembers_restriction_nonce'] ) || ! wp_verify_nonce( $_POST['imembers_restriction_nonce'], 'imembers_restriction_nonce_action' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        // Save restriction level
        if ( isset( $_POST['imembers_restriction_level'] ) ) {
            $level = sanitize_text_field( $_POST['imembers_restriction_level'] );
            update_post_meta( $post_id, '_imembers_restriction_level', $level );
            
            // Sync with old flag for compatibility
            if ( $level !== '0' ) {
                update_post_meta( $post_id, '_imembers_is_restricted', '1' );
            } else {
                delete_post_meta( $post_id, '_imembers_is_restricted' );
            }
        }
    }

    public function check_access() {
        // We only redirect NOT LOGGED IN users here.
        // Logged-in users with insufficient level will be handled by filter_content_by_level
        if ( is_user_logged_in() ) {
            return;
        }

        $is_restricted = false;

        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            if ( $this->get_post_level( $post_id ) > 0 ) {
                $is_restricted = true;
            } else {
                $categories = get_the_category( $post_id );
                if ( $categories ) {
                    foreach ( $categories as $cat ) {
                        if ( $this->get_term_level( $cat->term_id ) > 0 ) {
                            $is_restricted = true;
                            break;
                        }
                    }
                }
            }
        } else {
            // Check global archive restrictions
            $restricted_archives = get_option( 'imembers_restricted_archives', array() );
            if ( ! is_array( $restricted_archives ) ) $restricted_archives = array();

            if ( is_home() && in_array( 'home', $restricted_archives ) ) {
                $is_restricted = true;
            } elseif ( is_post_type_archive() ) {
                $post_type = get_query_var( 'post_type' );
                if ( is_array( $post_type ) ) $post_type = reset( $post_type );
                if ( in_array( $post_type, $restricted_archives ) ) {
                    $is_restricted = true;
                }
            } elseif ( is_category() || is_tag() || is_tax() ) {
                $term_id = get_queried_object_id();
                if ( $this->get_term_level( $term_id ) > 0 ) {
                    $is_restricted = true;
                }
            }
        }

        if ( $is_restricted ) {
            $this->redirect_to_login();
        }
    }

    /**
     * Filter content for logged-in users with insufficient access level
     */
    public function filter_content_by_level( $content ) {
        if ( ! is_singular() || is_admin() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id = get_the_ID();
        $required_level = $this->get_post_level( $post_id );

        // If post itself isn't restricted, check categories
        if ( $required_level === 0 ) {
            $categories = get_the_category( $post_id );
            if ( $categories ) {
                foreach ( $categories as $cat ) {
                    $cat_level = $this->get_term_level( $cat->term_id );
                    if ( $cat_level > $required_level ) {
                        $required_level = $cat_level;
                    }
                }
            }
        }

        if ( ! $this->user_meets_level( $required_level ) ) {
            // User is logged in (otherwise check_access would have redirected)
            // but doesn't meet the level requirement (e.g. Free user accessing Paid content)
            $login_page_id = get_option( 'imembers_page_imembers_login' );
            $login_url = $login_page_id ? get_permalink( $login_page_id ) : wp_login_url();
            
            // For logged-in users, redirect to My Page to see plans
            $upgrade_url = get_option( 'imembers_page_imembers_mypage' );
            if ( $upgrade_url ) $upgrade_url = get_permalink( $upgrade_url );

            ob_start();
            iMembers_Core::get_template( 'restriction-teaser', array( 
                'login_url'    => $login_url,
                'upgrade_url'  => $upgrade_url,
                'is_logged_in' => is_user_logged_in(),
                'required_level' => $required_level
            ) );
            return ob_get_clean();
        }

        return $content;
    }

    private function redirect_to_login() {
        // Determine login page ID or slug. We set it in installer to imembers-login
        $login_page_id = get_option( 'imembers_page_imembers_login' );
        $login_url = wp_login_url(); // Default
        
        if ( $login_page_id ) {
            $login_url = get_permalink( $login_page_id );
        }

        wp_redirect( add_query_arg( 'redirect_to', urlencode( home_url( $_SERVER['REQUEST_URI'] ) ), $login_url ) );
        exit;
    }

    public function members_only_shortcode( $atts, $content = null ) {
        $a = shortcode_atts( array(
            'level' => '', // 'paid' or empty
        ), $atts );

        $required_level = 1; // Default to free member
        if ( $a['level'] === 'paid' ) {
            $required_level = 2;
        } else {
            // If internal content, default to post/category level if set
            $post_id = get_the_ID();
            $post_level = $this->get_post_level( $post_id );
            if ( $post_level > 1 ) $required_level = $post_level;
        }

        if ( $this->user_meets_level( $required_level ) ) {
            return do_shortcode( $content );
        } else {
            // Suggest to login or upgrade
            $login_page_id = get_option( 'imembers_page_imembers_login' );
            $login_url = $login_page_id ? get_permalink( $login_page_id ) : wp_login_url();
            
            $upgrade_url = get_option( 'imembers_page_imembers_mypage' );
            if ( $upgrade_url ) $upgrade_url = get_permalink( $upgrade_url );

            ob_start();
            iMembers_Core::get_template( 'restriction-teaser', array( 
                'login_url'    => $login_url,
                'upgrade_url'  => $upgrade_url,
                'is_logged_in' => is_user_logged_in(),
                'required_level' => $required_level
            ) );
            return ob_get_clean();
        }
    }

    public function download_shortcode( $atts ) {
        $a = shortcode_atts( array(
            'url'   => '',
            'label' => 'ファイルをダウンロード',
            'level' => '', // 'paid' or empty
        ), $atts );

        $required_level = 1;
        if ( $a['level'] === 'paid' ) {
            $required_level = 2;
        }

        if ( ! $this->user_meets_level( $required_level ) ) {
            $msg = ( $required_level === 2 ) ? 'ダウンロードは有料会員限定です。' : 'ダウンロードは会員限定です。';
            return '<div class="imembers-download-block" style="padding:15px; border:1px dashed #ccc; background:#f9f9f9; text-align:center; border-radius:12px;">' .
                   '<p style="margin:0; font-weight:500;">' . esc_html( $msg ) . '</p>' .
                   '</div>';
        }

        return '<div class="imembers-download-block" style="padding:15px; border:1px solid var(--imembers-primary); background:var(--imembers-bg); text-align:center; border-radius:12px;">' .
               '<a href="' . esc_url( $a['url'] ) . '" class="imembers-btn imembers-btn-primary" download>' . esc_html( $a['label'] ) . '</a>' .
               '</div>';
    }

    /**
     * Term Restriction Fields (Add)
     */
    public function add_term_restriction_field() {
        ?>
        <div class="form-field">
            <?php wp_nonce_field( 'imembers_term_restriction_nonce', 'imembers_term_restriction_nonce_field' ); ?>
            <label for="imembers_restriction_level">閲覧権限</label>
            <select name="imembers_restriction_level" id="imembers_restriction_level">
                <option value="0">公開 (制限なし)</option>
                <option value="1">会員限定 (無料/有料)</option>
                <option value="2">有料会員限定</option>
            </select>
            <p>このカテゴリーに属する記事およびアーカイブの閲覧制限レベルを設定します。</p>
        </div>
        <?php
    }

    /**
     * Term Restriction Fields (Edit)
     */
    public function edit_term_restriction_field( $term ) {
        $level = get_term_meta( $term->term_id, '_imembers_restriction_level', true );
        
        // Backward compatibility
        if ( $level === '' && get_term_meta( $term->term_id, '_imembers_is_restricted', true ) === '1' ) {
            $level = '1';
        }
        if ( $level === '' ) $level = '0';
        ?>
        <tr class="form-field">
            <th scope="row"><label for="imembers_restriction_level">閲覧権限</label></th>
            <td>
                <?php wp_nonce_field( 'imembers_term_restriction_nonce', 'imembers_term_restriction_nonce_field' ); ?>
                <select name="imembers_restriction_level" id="imembers_restriction_level">
                    <option value="0" <?php selected( $level, '0' ); ?>>公開 (制限なし)</option>
                    <option value="1" <?php selected( $level, '1' ); ?>>会員限定 (無料/有料)</option>
                    <option value="2" <?php selected( $level, '2' ); ?>>有料会員限定</option>
                </select>
                <p class="description">このカテゴリーに属する記事およびアーカイブを制限します。</p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save Term Restriction Field
     */
    public function save_term_restriction_field( $term_id ) {
        if ( ! isset( $_POST['imembers_term_restriction_nonce_field'] ) || ! wp_verify_nonce( $_POST['imembers_term_restriction_nonce_field'], 'imembers_term_restriction_nonce' ) ) {
            return;
        }

        if ( isset( $_POST['imembers_restriction_level'] ) ) {
            $level = sanitize_text_field( $_POST['imembers_restriction_level'] );
            update_term_meta( $term_id, '_imembers_restriction_level', $level );
            
            // Sync with old flag for compatibility
            if ( $level !== '0' ) {
                update_term_meta( $term_id, '_imembers_is_restricted', '1' );
            } else {
                delete_term_meta( $term_id, '_imembers_is_restricted' );
            }
        }
    }
}
