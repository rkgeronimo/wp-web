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
    let transition = false;
    if (animation) {
        transition = 'transform 0.3s ease';
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
            boxSizing: 'border-box',
            position: 'absolute',
            top: (sizeVal * 0.1) + unit,
            left: (sizeVal * 0.1) + unit,
            height: (sizeVal * 0.8) + unit,
            width: (sizeVal * 0.8) + unit,
            borderRadius: '50%',
            borderTop: `${stroke} solid transparent`,
            borderRight: `${stroke} solid ${color}`,
            borderBottom: `${stroke} solid ${color}`,
            borderLeft: `${stroke} solid ${color}`,
            content: '""',
        },
        '&::after': {
            boxSizing: 'border-box',
            position: 'absolute',
            background: color,
            top: (sizeVal * 0.1) + unit,
            left: (sizeVal * 0.5) - (strokeVal / 2) + unit,
            width: strokeVal + unit,
            height: (sizeVal * 0.4) + unit,
            content: '""',
        },
    };
};
