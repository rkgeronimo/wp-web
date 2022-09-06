const defaults = require('./defaults.def.js');
const helpers = require('./helpers.def');

module.exports = (
    mixin,
    direction = 'top',
    color = defaults.color,
    size = defaults.size,
    stroke = defaults.stroke,
    animation = defaults.animation,
) => {
    const unit = helpers.unit(size);
    const sizeVal = helpers.value(size);
    const strokeVal = helpers.value(stroke);
    const colorDark = `color(${color} a(80%))`;
    const sizeSqrt = sizeVal / Math.sqrt(2);
    const borderRadius = (strokeVal / 2) + unit;
    let transition = false;
    let hover = false;
    if (animation) {
        transition = 'transform 0.3s ease';
        hover = {transform: 'rotate(360deg)'};
    }
    let top;
    let right;
    let bottom;
    let left;
    let transform;

    switch (direction.trim()) {
        case 'top':
            top = (sizeVal / 4) + ((sizeVal - (sizeVal / Math.sqrt(2))) / 2)
            - (Math.sqrt(2 * (strokeVal ** 2)) / 2) + unit;
            left = ((sizeVal - (sizeVal / Math.sqrt(2))) / 2) + unit;
            transform = 'rotate(45deg)';
            break;
        case 'right':
            top = ((sizeVal - (sizeVal / Math.sqrt(2))) / 2) + unit;
            right = (sizeVal / 4) + ((sizeVal - (sizeVal / Math.sqrt(2))) / 2)
            - (Math.sqrt(2 * (strokeVal ** 2)) / 2) + unit;
            transform = 'rotate(135deg)';
            break;
        case 'bottom':
            bottom = (sizeVal / 4) + ((sizeVal - (sizeVal / Math.sqrt(2))) / 2)
            - (Math.sqrt(2 * (strokeVal ** 2)) / 2) + unit;
            left = ((sizeVal - (sizeVal / Math.sqrt(2))) / 2) + unit;
            transform = 'rotate(-135deg)';
            break;
        case 'left':
            top = ((sizeVal - (sizeVal / Math.sqrt(2))) / 2) + unit;
            left = (sizeVal / 4) + ((sizeVal - (sizeVal / Math.sqrt(2))) / 2)
            - (Math.sqrt(2 * (strokeVal ** 2)) / 2) + unit;
            transform = 'rotate(-45deg)';
            break;
        default:
            break;
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
            width: sizeSqrt + unit,
            height: sizeSqrt + unit,
            top,
            right,
            bottom,
            left,
            boxSizing: 'border-box',
            borderTop: `${stroke} solid ${color}`,
            borderLeft: `${stroke} solid ${color}`,
            borderRadius,
            transform,
            content: '""',
        },
        '&:hover::before, &:hover::after': {
            borderTop: `${stroke} solid ${colorDark}`,
            borderLeft: `${stroke} solid ${colorDark}`,
        },
        '&:hover': hover,
    };
};
