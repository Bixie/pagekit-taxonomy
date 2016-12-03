
module.exports = [

    {
        entry: {
            "taxonomy": "./app/taxonomy.js",
        },
        output: {
            filename: "./app/bundle/[name].js",
        },
        module: {
            loaders: [
                {test: /\.vue$/, loader: "vue" },
                {test: /\.html$/, loader: "vue-html"},
                {test: /\.js/, loader: 'babel', query: {presets: ['es2015']}},
            ]
        }
    },

];
