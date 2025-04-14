import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ ssrBuild, mode }) => {
    const forceHttps = mode === 'production' || process.env.FORCE_HTTPS === 'true';
    const isSsr = ssrBuild === true;

    return {
        plugins: [
            laravel({
                input: 'resources/js/app.ts',
                ssr: 'resources/js/ssr.ts',
                refresh: true,

            }),
            vue(),
        ],

        server: {
            https: forceHttps,
        },
    };
});
