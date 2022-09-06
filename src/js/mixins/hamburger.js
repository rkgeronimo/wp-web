const defaults = require('./defaults.def.js');
const helpers = require('./helpers.def');

module.exports = (
    mixin,
    color = defaults.color,
    size = defaults.size,
    stroke = defaults.stroke,
    animation = defaults.animation,
) => {
    const unit = helpers.unit(size);
    const sizeVal = helpers.value(size);
    const strokeVal = helpers.value(stroke);
    const colorDark = `color(${color} a(80%))`;
    const boxShadow = `inset 0 0 0 ${stroke} ${color}, 0 -${sizeVal / 3}${unit}`
        + ` ${color}, 0 ${sizeVal / 3}${unit} ${color}`;
    const boxShadowDark = `inset 0 0 0 ${stroke} ${colorDark}, 0 -${sizeVal / 3}${unit}`
        + ` ${colorDark}, 0 ${sizeVal / 3}${unit} ${colorDark}`;
    let transition = false;
    let hover = {color: `color(${color} shade(20%))`};
    if (animation) {
        transition = 'transform 0.3s ease';
        hover = {transform: 'rotate(180deg)'};
    }
    return {
        position: 'relative',
        height: size,
        width: size,
        display: 'inline-block',
        verticalAlign: 'bottom',
        cursor: 'pointer',
        transition,
        '&::before': {
            position: 'absolute',
            width: size,
            height: stroke,
            top: (sizeVal / 2) - (strokeVal / 2) + unit,
            boxShadow,
            content: '""',
        },
        '&:hover::before': {
            boxShadow: boxShadowDark,
        },
        '&:hover': hover,
    };
};
