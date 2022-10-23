/* global rkgTheme loginStatus Croppie rkgScript PDF417 modalStatusClass */
let modalShow     = false;
let signup        = null;

const modalOpen = (modal, callback) => {
    modalStatusClass();
    $('.rkg-modal-status').text('');
    $('body').addClass('modal-open');

    if (modalShow) {
        $('.rkg-modal form, .modal-div').fadeOut('fast').promise().done(() => {
            $(modal).fadeIn(400);
        });
    } else {
        $('.rkg-modal form, .modal-div').hide();
        $(modal).show();
    }

    modalShow = true;
    if ($(window).width() <= 1080) {
        $('.rkg-modal')
            .fadeIn({queue: false, duration: 'fast'}).promise().done(callback);
        return;
    }
    $('.rkg-modal-background').fadeIn('fast').promise().done(() => {
        $('.rkg-modal')
            .fadeIn({queue: false, duration: 'fast'})
            .animate({top: '50%'})
            .promise()
            .done(callback);
    });
};

$('.ShowPayModal').click((e) => {
    e.preventDefault();
    const textToEncode = $(e.currentTarget).data('barcode_text');
    PDF417.init(textToEncode);

    const barcode = PDF417.getBarcodeArray();

    // block sizes (width and height) in pixels
    const bw = 2;
    const bh = 1;

    // create canvas element based on number of columns and rows in barcode
    const container = document.getElementById('payment-barcode');
    if (container.firstChild) {
        container.removeChild(container.firstChild);
    }

    const canvas = document.createElement('canvas');
    canvas.width = bw * barcode.num_cols;
    canvas.height = bh * barcode.num_rows;
    container.appendChild(canvas);

    const ctx = canvas.getContext('2d');

    // graph barcode elements
    let y = 0;
    // for each row
    for (let r = 0; r < barcode.num_rows; ++r) {
        let x = 0;
        // for each column
        for (let c = 0; c < barcode.num_cols; ++c) {
            if (barcode.bcode[r][c] === '1') {
                ctx.fillRect(x, y, bw, bh);
            }
            x += bw;
        }
        y += bh;
    }

    const title = $(e.currentTarget).data('title');
    const primatelj = $(e.currentTarget).data('primatelj');
    const iban = $(e.currentTarget).data('iban');
    const modelPlacanja = $(e.currentTarget).data('model_placanja');
    const pozivNaBroj = $(e.currentTarget).data('poziv_na_broj');
    const opisPlacanja = $(e.currentTarget).data('opis_placanja');
    const iznos = $(e.currentTarget).data('iznos');
    $('#payment-title').html(title);
    $('#payment-primatelj').html(primatelj);
    $('#payment-iban').html(iban);
    $('#payment-model_placanja').html(modelPlacanja);
    $('#payment-poziv_na_broj').html(pozivNaBroj);
    $('#payment-opis_placanja').html(opisPlacanja);
    $('#payment-iznos').html(iznos);

    // $('.rkg-profile-meni').hide()
    // .promise()
    // .done(() => {
    // modalOpen('#payment-modal');
    // });
});

$('.rkg-modal-close, rkg-info-close').click((e) => {
    modalShow = false;
    e.preventDefault();
    modalStatusClass();
    $('.rkg-modal-status').text('');
    $('body').removeClass('modal-open');
    signup = null;
    if ($(window).width() <= 1080) {
        $('.rkg-modal')
            .fadeOut({queue: false, duration: 'fast'})
            .promise()
            .done(() => {
                $('.rkg-modal form, .modal-div').hide();
            });
        return;
    }
    $('.rkg-modal')
        .fadeOut({queue: false, duration: 'fast'})
        .animate({top: '37.5%'})
        .promise()
        .done(() => {
            $('.rkg-modal form, .modal-div').hide();
            $('.rkg-modal-background').fadeOut('fast');
        });
});

$('.course-signup-ok-close').click((e) => {
    modalShow = false;
    e.preventDefault();
    modalStatusClass();
    $('.rkg-modal-status').text('');
    $('body').removeClass('modal-open');
    signup = null;
    if ($(window).width() <= 1080) {
        $('.rkg-modal')
            .fadeOut({queue: false, duration: 'fast'})
            .promise()
            .done(() => {
                $('.rkg-modal form, .modal-div')
                    .hide()
                    .promise()
                    .done(() => {
                        window.location.reload();
                    });
            });
        return;
    }
    $('.rkg-modal')
        .fadeOut({queue: false, duration: 'fast'})
        .animate({top: '37.5%'})
        .promise()
        .done(() => {
            $('.rkg-modal form, .modal-div').hide();
            $('.rkg-modal-background')
                .fadeOut('fast')
                .hide()
                .promise()
                .done(() => {
                    window.location.reload();
                });
        });
});

if ($('#no-required-user').length !== 0) {
    modalOpen('#registration');
}

$('.rkg-login-button, .rkg-login-show').click(() => {
    modalOpen('#login');
});

$('.rkg-registration-show').click(() => {
    modalOpen('#registration');
});

$('.rkg-lost-password-show').click(() => {
    modalOpen('#lost-password');
});

$('#rkg-members-button').click(() => {
    if ($('#rkg-members-table-container').is(':empty')) {
        $.ajax({
            type: 'POST',
            url: rkgTheme.ajaxurl,
            data: {
                action: 'getMembersList',
            },
            success(data) {
                $('#rkg-members-table-container').html(data);
            },
        });
    }

    if (($(window).width() <= 1080) && (window.devicePixelRatio > 1.5)) {
        $('.rkg-profile-meni').css('left', '-90vw')
            .promise()
            .done(() => {
                modalOpen('#rkg-moddal-members');
            });
        return;
    }
    $('.rkg-profile-meni').animate({
        height: 'toggle',
        opacity: 'toggle',
    }, 'fast')
        .promise()
        .done(() => {
            modalOpen('#rkg-moddal-members');
        });
});

$('.course-signup').on('click', (e) => {
    e.preventDefault();
    signup = 'course';
    const signupId = $(e.currentTarget).data('course');
    const signupName = $(e.currentTarget).data('name');
    const signupDate = $(e.currentTarget).data('date');
    const link = $(e.currentTarget).data('link');
    $('input[name="signup-course"]').val(signupId);
    $('.course-signup-name').text(signupName);
    $('.course-signup-date').text(signupDate);
    $('#course-signup-modal-link').attr('href', link);
    jQuery.post(rkgTheme.ajaxurl, loginStatus, (response) => {
        if (response === 'yes') {
            const dob = $('#dob').val();
            const oib = $('#oib').val();
            const tel = $('#tel').val();
            if ((dob.length === 0) || (oib.length === 0) || (tel.length === 0)) {
                modalOpen('#additional-details-form');
            } else {
                $('#additional-details-form').submit();
            }
        } else {
            modalOpen('#registration');
        }
    });
});

$('form#login').on('submit', (e) => {
    modalStatusClass();
    $('.rkg-modal-status').text('Prijava u tijeku...');
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'ajaxlogin',
            username: $('form#login #username').val(),
            password: $('form#login #password').val(),
            security: $('form#login #security').val(),
        },
        success(data) {
            modalStatusClass(data.status);
            $('.rkg-modal-status').text(data.message);
            if (data.status === 0) {
                if (signup === 'course') {
                    modalOpen('#additional-details-form');
                    signup = null;
                } else {
                    window.location.reload();
                }
            }
        },
        error: (error) => {
            console.log(error);
        },
    });
    e.preventDefault();
});

$('form#registration').on('submit', (e) => {
    modalStatusClass();
    $('.rkg-modal-status').text('Registracija u tijeku...');
    const regNonce     = $('#vb_new_user_nonce').val();
    const regPass      = $('#vb_pass').val();
    const regMail      = $('#vb_email').val();
    const regFirstname = $('#vb_name').val();
    const regLastname  = $('#vb_surname').val();
    const data         = {
        action: 'register_user',
        nonce: regNonce,
        pass: regPass,
        mail: regMail,
        firstname: regFirstname,
        lastname: regLastname,
    };

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data,
        success(data) {
            modalStatusClass(data.status);
            $('.rkg-modal-status').text(data.message);
            if (data.status === 0) {
                if (signup === 'course') {
                    modalOpen('#additional-details-form');
                    signup = null;
                } else {
                    window.location.reload();
                }
            }
        },
        error: (error) => {
            console.log(error);
        },
    });

    e.preventDefault();
});

$('form#additional-details-form').on('submit', (e) => {
    modalStatusClass();
    $('.rkg-modal-status').text(rkgTheme.loadingmessage);
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'rkg_user_additional_details',
            pob: $('form#additional-details-form #pob').val(),
            dob: $('form#additional-details-form #dob').val(),
            oib: $('form#additional-details-form #oib').val(),
            tel: $('form#additional-details-form #tel').val(),
            course: $('form#additional-details-form #signup-course').val(),
        },
        success: (data) => {
            modalStatusClass(data.status);
            $('form#additional-details-form p.status').text(data.message);
            modalOpen('#course-signup-ok');
        },
    });
    e.preventDefault();
});

$('.rkg-course-signup-close').click(() => {
    $('.rkg-course-signup').fadeOut({queue: false, duration: 'fast'})
        .animate({top: '25%'}, () => {
            $('.rkg-modal-background').fadeOut(
                'fast',
                () => window.location.reload(),
            );
        });

    if ($(window).width() <= 1080) {
        $('.rkg-modal')
            .fadeOut({queue: false, duration: 'fast'})
            .promise()
            .done(() => {
                window.location.reload();
            });
        return;
    }
    $('.rkg-modal')
        .fadeOut({queue: false, duration: 'fast'})
        .animate({top: '37.5%'})
        .promise()
        .done(() => {
            window.location.reload();
        });
});

$('.course-signout').on('click', (e) => {
    e.preventDefault();
    const signupId = $(e.currentTarget).data('course');
    const signoutName = $(e.currentTarget).data('name');
    $('input[name="signout-course"]').val(signupId);
    $('.course-signout-name').text(signoutName);
    modalOpen('#course-signout-form');
});

$('#course-signup-ok-pay, #course-signup-show-pay').click((e) => {
    modalOpen('#course-signup-pay');
    e.preventDefault();
});

$('#helth-survey-link').click((e) => {
    modalOpen('#helth-survey');
    e.preventDefault();
});

$('#responsibility-survey-link').click((e) => {
    modalOpen('#responsibility-survey');
    e.preventDefault();
});

const resize = new Croppie(document.getElementById('brevet-crop'), {
    viewport: {width: 257, height: 300},
    boundary: {width: 320, height: 363},
    showZoomer: true,
    enableOrientation: true,
    url: 'http://lorempixel.com/500/400/',
});

$('.croppie-rotate').on('click', (e) => {
    resize.rotate(parseInt($(e.currentTarget).data('deg'), 10));
});

function readURL(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            // $('.croppie-container').html('');
            // $('#brevet-crop')[0].croppie('destroy');
            modalOpen('#rkg-moddal-brevet', () => {
                console.log(e);
                resize.bind({
                    url: e.target.result,
                });
                $('.brevet-upload-button').on('click', () => {
                    resize.result('base64').then((dataImg) => {
                        // const data = [{image: dataImg}, {name: 'myimgage.jpg'}];
                        const formData = new FormData();
                        formData.append('action', 'brevet_upload');
                        formData.append('image', dataImg);
                        jQuery.ajax({
                            url: rkgScript.ajaxUrl,
                            type: 'POST',
                            contentType: false,
                            processData: false,
                            data: formData,
                            success(response) {
                                console.log(response);
                                $('.course-brevet-image').html(`<img src="${response}" />`);
                                modalStatusClass();
                                $('.rkg-modal-status').text('');
                                $('body').removeClass('modal-open');
                                signup = null;
                                if ($(window).width() <= 1080) {
                                    $('.rkg-modal')
                                        .fadeOut({queue: false, duration: 'fast'})
                                        .promise()
                                        .done(() => {
                                            window.location.reload();
                                        });
                                    return;
                                }
                                $('.rkg-modal')
                                    .fadeOut({queue: false, duration: 'fast'})
                                    .animate({top: '37.5%'})
                                    .promise()
                                    .done(() => {
                                        window.location.reload();
                                    });
                            },
                        });
                        $('#result').attr('src', dataImg);
                    });
                });
            });
        };
        reader.readAsDataURL(input.files[0]);
    }
}

$('#brevet').change(function () {
    if (!window.File || !window.FileReader || !window.FileList || !window.Blob) {
        alert('The File APIs are not fully supported in this browser.');
        return;
    }
    readURL(this);
});

$('#brevet-link').click((e) => {
    e.preventDefault();
    $('#brevet').click();
});

$('form#helth-survey').on('submit', (e) => {
    e.preventDefault();
    const formData  = new FormData($('#helth-survey').get(0));
    console.log(rkgTheme.ajaxurl);
    formData.append('action', 'health_survey');

    jQuery.ajax({
        url: rkgTheme.ajaxurl,
        type: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        success() {
            window.location.reload();
        },
        error(response) {
            console.log(response);
        },
    });
});

$('form#responsibility-survey').on('submit', (e) => {
    e.preventDefault();
    const formData  = new FormData($('#responsibility-survey').get(0));
    console.log(rkgTheme.ajaxurl);
    formData.append('action', 'responsibility_survey');

    jQuery.ajax({
        url: rkgTheme.ajaxurl,
        type: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        success() {
            window.location.reload();
        },
        error(response) {
            console.log(response);
        },
    });
});

$('.excursion-signup').click((e) => {
    e.preventDefault();
    const signupId = $(e.currentTarget).data('post');

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'rkg_user_excursion_signup',
            post: signupId,
        },
        success: () => {
            modalOpen('#excursion-signup-ok');
        },
        error: (error) => {
            console.log(error);
        },
    });
});

$('#excursion-signup-ok-gear, #excursion-signup-gear').click((e) => {
    e.preventDefault();
    modalOpen('#gear-form');
});

$('.excursion-signout').click((e) => {
    e.preventDefault();
    signup = 'course';
    const signupId = $(e.currentTarget).data('post');
    const signoutName = $(e.currentTarget).data('name');
    $('input[name="signout-excursion"]').val(signupId);
    $('.excursion-signout-name').text(signoutName);
    jQuery.post(rkgTheme.ajaxurl, loginStatus, (response) => {
        if (response === 'yes') {
            modalOpen('#excursion-signout-form');
        }
    });
});

$('.excursion-signup-waiting').click((e) => {
    e.preventDefault();
    const signupId = $(e.currentTarget).data('post');

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'rkg_user_excursion_signup_waiting',
            post: signupId,
        },
        success: () => {
            modalOpen('#excursion-signup-ok');
        },
        error: (error) => {
            console.log(error);
        },
    });
});

$('.excursion-signout-waiting').click((e) => {
    e.preventDefault();
    signup = 'course';
    const signupId = $(e.currentTarget).data('post');
    const signoutName = $(e.currentTarget).data('name');
    $('input[name="signout-excursion"]').val(signupId);
    $('.excursion-signout-name').text(signoutName);
    jQuery.post(rkgTheme.ajaxurl, loginStatus, (response) => {
        if (response === 'yes') {
            modalOpen('#excursion-signout-waiting-form');
        }
    });
});

$('form#excursion-signout-form').on('submit', (e) => {
    $('form#excursion-signout-form p.status').show()
        .text('Odjava u tijeku...');
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'rkg_excursion_signout',
            post: $('form#excursion-signout-form #signout-excursion').val(),
        },
        success: (data) => {
            $('form#excursion-signout-form p.status').text(data.message);
            window.location.reload();
        },
    });
    e.preventDefault();
});

$('form#excursion-signout-waiting-form').on('submit', (e) => {
    $('form#excursion-signout-form p.status').show()
        .text('Odjava u tijeku...');
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'rkg_excursion_signout_waiting',
            post: $('form#excursion-signout-form #signout-excursion').val(),
        },
        success: (data) => {
            $('form#excursion-signout-form-waiting p.status').text(data.message);
            window.location.reload();
        },
    });
    e.preventDefault();
});

$('form#gear-form').on('submit', (e) => {
    e.preventDefault();
    const formData  = new FormData($('#gear-form').get(0));
    formData.append('action', 'gear_reserve');

    jQuery.ajax({
        url: rkgTheme.ajaxurl,
        type: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        success() {
            window.location.reload();
        },
        error(response) {
            console.log(response);
        },
    });
});

$('#no-gear').click((e) => {
    e.preventDefault();
    const gearId = $(e.currentTarget).data('post');
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'gear_reserve_no',
            post: gearId,
        },
        success: () => {
            window.location.reload();
        },
    });
});

$('#excursion-signup-guest').click((e) => {
    modalOpen('#excursion-guest-form');
    e.preventDefault();
});

$('form#excursion-guest-form').on('submit', (e) => {
    e.preventDefault();
    const formData  = new FormData($('#excursion-guest-form').get(0));
    formData.append('action', 'guest_invite');

    jQuery.ajax({
        url: rkgTheme.ajaxurl,
        type: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        success() {
            window.location.reload();
        },
        error(response) {
            console.log(response);
        },
    });
});

$('.guest-uninvite').click((e) => {
    e.preventDefault();
    const name = $(e.currentTarget).data('name');
    const email = $(e.currentTarget).data('email');
    $('#guest-remove-name').html(name);
    $('#guest-remove-email').val(email);
    modalOpen('#excursion-guest-remove-form');
});

$('form#excursion-guest-remove-form').on('submit', (e) => {
    e.preventDefault();
    const formData  = new FormData($('#excursion-guest-remove-form').get(0));
    formData.append('action', 'guest_uninvite');

    jQuery.ajax({
        url: rkgTheme.ajaxurl,
        type: 'POST',
        contentType: false,
        processData: false,
        data: formData,
        success() {
            window.location.reload();
        },
        error(response) {
            console.log(response);
        },
    });
});

$('.course-interested').click((e) => {
    e.preventDefault();
    const course = $(e.currentTarget).data('course');
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'course_interest',
            course,
        },
        success() {
            modalOpen('#course-interested-form');
        },
        error: (error) => {
            console.log(error);
        },
    });
});

$('.course-not-interested').click((e) => {
    e.preventDefault();
    const course = $(e.currentTarget).data('course');
    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: rkgTheme.ajaxurl,
        data: {
            action: 'course_not_interest',
            course,
        },
        success() {
            modalOpen('#course-not-interested-form');
        },
        error: (error) => {
            console.log(error);
        },
    });
});
