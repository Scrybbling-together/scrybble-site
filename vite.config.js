import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import sentryVitePlugin from "@sentry/vite-plugin";
import dotenv from 'dotenv';
import { execSync } from 'child_process'

dotenv.config();

const GIT_HASH = process.env.RELEASE_HASH ?? JSON.stringify(execSync('git rev-parse --short HEAD').toString().trim());


let vitePlugin = sentryVitePlugin({
    org: "streamsoft",
    project: "scrybble-frontend",
    release: `scrybble-${GIT_HASH}`,

    // Specify the directory containing build artifacts
    include: '.',
    ignore: ['node_modules', 'vite.config.ts'],
    silent: true,
    telemetry: true,
    sourceMapReference: false,
    sourceMaps: {
        include: ['./public/build/assets'],
        ignore: ['node_modules'],
        urlPrefix: '~/assets',
    },
    // Auth tokens can be obtained from https://sentry.io/settings/account/api/auth-tokens/
    // and needs the `project:releases` and `org:read` scopes
    authToken: process.env.SENTRY_AUTH_TOKEN,

    // Optionally uncomment the line below to override automatic release name detection
    // release: process.env.RELEASE,
});
export default ({mode}) => {
    return defineConfig({
        plugins: [
            laravel({
                input: [
                    'resources/sass/app.scss',
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
            process.env.APP_DEBUG ? null : vitePlugin,
        ],
        resolve: {
            alias: {
                "@": "/resources/ts"
            }
        }
    });
};
