import * as esbuild from 'esbuild'
import { readdir } from 'fs/promises'
import { join } from 'path'

async function buildRichEditorPlugins() {
    try {
        // Find all JavaScript plugin files
        const pluginsDir = './resources/js/filament/rich-content-plugins'
        const files = await readdir(pluginsDir)
        const pluginFiles = files.filter(file => file.endsWith('.js'))
        
        if (pluginFiles.length === 0) {
            console.log('No rich editor plugin files found to build.')
            return
        }

        console.log(`Found ${pluginFiles.length} plugin file(s) to build:`)
        pluginFiles.forEach(file => console.log(`  - ${file}`))

        // Create entry points for each plugin
        const entryPoints = {}
        pluginFiles.forEach(file => {
            const fileName = file.replace('.js', '')
            entryPoints[`filament/rich-content-plugins/${fileName}`] = join(pluginsDir, file)
        })

        const context = await esbuild.context({
            define: {
                'process.env.NODE_ENV': `'production'`,
            },
            bundle: true,
            mainFields: ['module', 'main'],
            platform: 'neutral',
            sourcemap: false,
            sourcesContent: false,
            treeShaking: true,
            target: ['es2020'],
            minify: true,
            entryPoints,
            outdir: './resources/js/dist',
            format: 'esm',
        })

        await context.rebuild()
        await context.dispose()

        console.log('✅ Rich editor plugins built successfully!')
        console.log('Built files:')
        Object.keys(entryPoints).forEach(key => {
            console.log(`  - resources/js/dist/${key}.js`)
        })
    } catch (error) {
        console.error('❌ Error building rich editor plugins:', error)
        process.exit(1)
    }
}

buildRichEditorPlugins()
