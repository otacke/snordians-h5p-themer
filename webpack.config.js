import { dirname, resolve as _resolve, join } from 'path';
import { fileURLToPath } from 'url';
import TerserPlugin from 'terser-webpack-plugin'; // Provided by webpack

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const mode = process.argv.includes('--mode=production') ?
  'production' :
  'development';

export default {
  mode: mode,
  resolve: {
    alias: {
      '@assets': _resolve(__dirname, 'src/assets'),
      '@components': _resolve(__dirname, 'src/scripts/components'),
      '@mixins': _resolve(__dirname, 'src/scripts/mixins'),
      '@models': _resolve(__dirname, 'src/scripts/models'),
      '@root': _resolve(__dirname, './'),
      '@scripts': _resolve(__dirname, 'src/scripts'),
      '@services': _resolve(__dirname, 'src/scripts/services'),
      '@styles': _resolve(__dirname, 'src/styles')
    }
  },
  optimization: {
    minimize: mode === 'production',
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          compress: {
            drop_console: true,
          }
        }
      })
    ]
  },
  entry: {
    'h5p-themer': './src/scripts/h5p-themer.js',
    'h5p-theme-picker-loader': './src/scripts/h5p-theme-picker-loader.js'
  },
  output: {
    filename: '[name].js',
    path: _resolve(__dirname, 'js'),
    clean: true
  },
  target: ['browserslist'],
  module: {
    rules: [
      {
        test: /\.(css)$/,
        use: [
          {
            loader: 'css-loader'
          }
        ]
      }
    ]
  },
  stats: {
    colors: true
  },
  ...(mode !== 'production' && { devtool: 'eval-cheap-module-source-map' })
};
