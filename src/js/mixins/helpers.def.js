module.exports.unit = x => x.match(/[a-zA-Z]+/g)[0];
module.exports.value = x => x.match(/[0-9&.]+/g)[0];
