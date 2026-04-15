jQuery(document).ready(function($) {
    // Favorite Button Click
    $(document).on('click', '.imembers-favorite-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var postId = $btn.data('id');

        if ($btn.hasClass('processing')) return;
        $btn.addClass('processing');

        $.ajax({
            url: imembers_ajax.url,
            type: 'POST',
            data: {
                action: 'imembers_toggle_favorite',
                post_id: postId,
                nonce: imembers_ajax.nonce
            },
            success: function(response) {
                $btn.removeClass('processing');
                if (response.success) {
                    if (response.data.action === 'added') {
                        $btn.addClass('favorited').text('★ お気に入り解除');
                    } else {
                        $btn.removeClass('favorited').text('☆ お気に入り登録');
                    }
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                $btn.removeClass('processing');
                alert('通信エラーが発生しました。');
            }
        });
    });
});
