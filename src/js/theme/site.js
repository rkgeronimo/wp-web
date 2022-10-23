/* global rkgTheme Croppie */

jQuery(document).ready(($) => {
    //=require ../../../node_modules/pdf417/bcmath-min.js
    //=require ../../../node_modules/pdf417/pdf417.js
    let sequence;
    let codewords;

    function modalStatusClass(s = 'na') {
        $('.rkg-modal-status').removeClass('error ok');
        if (s === 1) {
            $('.rkg-modal-status').addClass('error');
        } else if (s === 0) {
            $('.rkg-modal-status').addClass('ok');
        }
    }

    const loginStatus = {action: 'is_user_logged_in'};
    $('.rkg-meni-open').click(() => {
        $('body').addClass('modal-open');
        $('.rkg-meni-background').fadeIn(200, () => {
            $('.rkg-meni').fadeIn({queue: false, duration: 200})
                .animate({bottom: '0', top: '0'}, 200);
        });
        $('#rkg-top-meni').fadeOut('fast');
        $('#rkg-header-meni').fadeOut('fast');
    });

    $('.rkg-meni-close, #nav-main a').click(() => {
        $('body').removeClass('modal-open');
        $('.rkg-meni').fadeOut({queue: false, duration: 200})
            .animate({bottom: '50%', top: '-50%'}, 200, () => {
                $('.rkg-meni-background').fadeOut(200);
            });
        $('#rkg-top-meni').fadeIn('fast');
        $('#rkg-header-meni').fadeIn('fast');
    });

    //= include modal.js
    //= include cookie.js

    // $(document).mouseup((e) => {
    // if ($('.rkg-profile-meni').css('display') === 'block'
    // && !$('.rkg-profile-meni').is(e.target)
    // && !$('.rkg-profile-meni-switch').is(e.target)
    // && $('.rkg-profile-meni').has(e.target).length === 0) {
    // $('.rkg-profile-meni').animate({
    // height: 'toggle',
    // opacity: 'toggle',
    // }, 'fast');
    // }
    // });

    $('.rkg-profile-meni-switch').click(() => {
        if (($(window).width() <= 1080) && (window.devicePixelRatio > 1.5)) {
            $('.rkg-profile-meni').css('left', '0vw');
            return;
        }
        $('.rkg-profile-meni').animate({
            height: 'toggle',
            opacity: 'toggle',
        }, 'fast');
    });

    $('.rkg-profile-meni-close').click(() => {
        if (($(window).width() <= 1080) && (window.devicePixelRatio > 1.5)) {
            $('.rkg-profile-meni').css('left', '-90vw');
            return;
        }
        $('.rkg-profile-meni').animate({
            height: 'toggle',
            opacity: 'toggle',
        }, 'fast');
    });

    // $(document).mouseup((e) => {
    // if (!$('.rkg-profile-meni').is(e.target)
    // && $('.rkg-profile-meni').has(e.target).length === 0) {
    // $('.rkg-profile-meni').css('left', '-90vw');
    // }
    // });

    const sticky = $('#rkg-top-stick').offset();

    const rkgStick = () => {
        if (window.pageYOffset > sticky.top) {
            $('#rkg-top-stick').addClass('sticky');
            $('.rkg-front-hide').fadeIn('fast');
        } else {
            $('#rkg-top-stick').removeClass('sticky');
            $('.rkg-front-hide').fadeOut(100);
        }
    };

    window.onscroll = () => rkgStick();

    $('#rkg-header-arrow-container, #rkg-ancor-1-go').click(() => {
        document.getElementById('rkg-scroll-ancor')
            .scrollIntoView({behavior: 'smooth', block: 'start'});
    });
    $('#rkg-ancor-2-go').click(() => {
        document.getElementById('rkg-ancor-2')
            .scrollIntoView({behavior: 'smooth', block: 'start'});
    });
    $('#rkg-ancor-3-go').click(() => {
        document.getElementById('rkg-ancor-3')
            .scrollIntoView({behavior: 'smooth', block: 'start'});
    });
    $('#rkg-ancor-4-go').click(() => {
        document.getElementById('rkg-ancor-4')
            .scrollIntoView({behavior: 'smooth', block: 'start'});
    });

    $('form#course-signout-form').on('submit', (e) => {
        modalStatusClass();
        $('.rkg-modal-status').text('Odjava u tijeku...');
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: rkgTheme.ajaxurl,
            data: {
                action: 'rkg_course_signout',
                course: $('form#course-signout-form #signout-course').val(),
            },
            success: (data) => {
                modalStatusClass(0);
                $('.rkg-modal-status').text(data.message);
                window.location.reload();
            },
        });
        e.preventDefault();
    });

    $('form#lost-password').on('submit', (e) => {
        e.preventDefault();
        modalStatusClass();
        $('.rkg-modal-status').text('Slanje u tijeku...');
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: rkgTheme.ajaxurl,
            data: {
                action: 'sendPasswordReset',
                lost_username: $('#lost_username').val(),
            },
            success: (data) => {
                modalStatusClass(data.status);
                $('.rkg-modal-status').text(data.message);
            },
            error: (error) => {
                console.log(error);
            },
        });
    });

    $('.courses-terms-control').click((e) => {
        const cat = $(e.currentTarget).data('cat');
        const state = $(e.currentTarget).data('state');
        if (state === 'off') {
            $('.courses-terms-up').hide();
            $('.courses-terms-down').show();
            $(`.courses-terms-down-${cat}`).hide();
            $(`.courses-terms-up-${cat}`).show();
            $('.courses-terms').hide();
            $(`.courses-terms-${cat}`).css('display', 'block');
            $('.courses-terms-control').data('state', 'off');
            $(e.currentTarget).data('state', 'on');
        } else {
            $('.courses-terms-up').hide();
            $('.courses-terms-down').show();
            $('.courses-terms').hide();
            $(e.currentTarget).data('state', 'off');
        }
    });

    $('.rkg-excursion-signout-close').click(() => {
        $('.rkg-excursion-signout').fadeOut({queue: false, duration: 'fast'})
            .animate({top: '25%'}, () => {
                $('.rkg-modal-background').fadeOut(
                    'fast',
                    () => window.location.reload(),
                );
            });
    });

    $('.rkg-moddal-gallery-slick').slick({
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        prevArrow: $('.rkg-moddal-gallery-chevron-left'),
        nextArrow: $('.rkg-moddal-gallery-chevron-right'),
    });

    $('.gallery-item').click((e) => {
        const gallery = $(e.currentTarget).parent();
        $('.rkg-meni-background').fadeIn('fast').promise().done(() => {
            $('.rkg-moddal-gallery')
                .fadeIn({queue: false, duration: 'fast'});
            gallery.children('img').each((index, img) => {
                const src = $(img).attr('src');
                $('.rkg-moddal-gallery-slick').slick(
                    'slickAdd',
                    '<div class="rkg-moddal-gallery-slide">'
                    + `<img src='${src}'>`
                    + '</div>',
                );
            });
        });
    });

    $('.rkg-moddal-gallery-close').click(() => {
        $('.rkg-moddal-gallery').fadeOut('fast').promise().done(() => {
            $('.rkg-moddal-gallery-slick').slick('slickRemove', null, null, true);
            $('.rkg-meni-background')
                .fadeOut('fast');
        });
    });

    if ($('#rkg-users-input').length) {
        $('#rkg-users-input').on('keyup', function () {
            const value = $(this).val().toLowerCase();
            $('#rkg-users tr, #rkg-users-mob tbody').filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    }

    $('.rkg-user').click((e) => {
        const target = $(e.currentTarget).find('.rkg-user-details');
        $('.rkg-user-details').not(target).hide();
        target.animate({
            height: 'toggle',
            opacity: 'toggle',
        }, 'fast');
    });

    $(document).mouseup((e) => {
        if (!$('.rkg-user').is(e.target)
            && $('.rkg-user-details').has(e.target).length === 0) {
            $('.rkg-user-details').hide();
        }
    });

    $('#rkg-excursion-calendar').on('click', '.calendar-excursion', (e) => {
        const target = $(e.currentTarget).find('.date-excursions');
        $('.date-excursions').not(target).hide();
        target.animate({
            height: 'toggle',
            opacity: 'toggle',
        }, 'fast');
    });

    $(document).mouseup((e) => {
        if (!$('.calendar-excursion').is(e.target)
            && $('.date-excursions').has(e.target).length === 0) {
            $('.date-excursions').hide();
        }
    });

    $(window).scroll(() => {
        $('.rkg-user-details').hide();
    });

    $('.rkg-profile-meni-toggle').click((e) => {
        e.preventDefault();
        $(e.currentTarget).next().slideToggle();
    });

    function getParameterByName(name, url = window.location.href) {
        const rename = name.replace(/[\[\]]/g, '\\$&');
        const regex = new RegExp(`[?&]${rename}(=([^&#]*)|&|#|$)`);
        const results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    $('#rkg-excursion-calendar').on('click', '.rkg-cal-prev, .rkg-cal-next', (e) => {
        e.preventDefault();
        const url = $(e.currentTarget).attr('href');
        const month = getParameterByName('month', url);
        const year = getParameterByName('year', url);
        $.ajax({
            type: 'GET',
            url: rkgTheme.ajaxurl,
            data: {
                action: 'excursion_calendar',
                month,
                year,
            },
            success: (response) => {
                $('#rkg-excursion-calendar').html(response);
            },
        });
    });

    $('#excursion-contorl-search').click(() => {
        $('#rkg-excursion-page-search').toggle();
        $('.rkg-excursion-search-down').toggle();
        $('.rkg-excursion-search-up').toggle();
        $('.rkg-excursion-cal-down').show();
        $('.rkg-excursion-cal-up').hide();
    });
});
