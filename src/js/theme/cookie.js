/* global modalOpen */

function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = `expires=${d.toUTCString()}`;
    if (exdays === 0) {
        expires = '';
    }
    console.log(expires);
    document.cookie = `${cname}=${cvalue};${expires};path=/`;
}

function getCookie(cname) {
    const name = `${cname}=`;
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return '';
}

function checkBetaCookie() {
    const betaMessage = getCookie('betaMessage');
    if (betaMessage === '') {
        modalOpen('#beta-message');
        setCookie('betaMessage', true, 0);
    }
}

checkBetaCookie();

