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
    let transition = false;
    let hover = false;
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
        '&::before, &::after': {
            position: 'absolute',
            top: (sizeVal / 2) - (strokeVal / 2) + unit,
            left: 0,
            width: size,
            height: stroke,
            background: color,
            content: '""',
        },
        '&:hover::before, &:hover::after': {
            background: colorDark,
        },
        '&::before': {
            transform: 'rotate(45deg)',
        },
        '&::after': {
            transform: 'rotate(-45deg)',
        },
        '&:hover': hover,
    };
};
