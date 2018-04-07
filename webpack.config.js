const { isArray, mergeWith } = require('lodash')
const webpack = require('webpack')

function mergeWithFn(obj, src) {
    if (isArray(obj)) {
        return obj.concat(src)
    }
}

function createVarient(filename, config = {}) {
    return mergeWith(config, {
        mode: 'production',
        output: {
            filename,
            publicPath: '/dist/'
        },
        module: {
            rules: [
                { test: /bootstrap.+\.(jsx|js)$/, loader: 'imports-loader?jQuery=jquery,$=jquery,this=>window' }
            ]
        },
        plugins: [
            new webpack.ProvidePlugin({
                Helper: './Helper',
                I18n: './I18n',
                CryptTool: './CryptTool',
                Model: './Model',
                UiHelper: './UiHelper',
                Alert: './Alert',
                PasteStatus: './PasteStatus',
                Prompt: './Prompt',
                Editor: './Editor',
                PasteViewer: './PasteViewer',
                AttachmentViewer: './AttachmentViewer',
                DiscussionViewer: './DiscussionViewer',
                TopNav: './TopNav',
                Uploader: './Uploader',
                PasteEncrypter: './PasteEncrypter',
                PasteDecrypter: './PasteDecrypter',
                Controller: './Controller'
            })
        ]
    }, mergeWithFn)
}

module.exports = [
    createVarient('privatebin.js'),

    createVarient('zerobin.js', {
        plugins: [
            new webpack.NormalModuleReplacementPlugin(/js-base64/, function (resource) {
                resource.request = resource.request.replace('js-base64', 'js-base64/old/base64-1.7')
            })
        ]
    })
]
