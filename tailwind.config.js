const preset = require('./vendor/filament/filament/tailwind.config.preset')

module.exports = {
    presets: [preset],
    content: [
        './src/Filament/**/*.php',
        './resources/views/filament/**/*.php',
        './vendor/filament/**/*.php',
    ],
}
