module.exports = {
  singleQuote: true,
  useTabs: false,
  trailingComma: 'es5',

  phpVersion: '8.2',
  plugins: ['@prettier/plugin-php'],
  overrides: [
    {
      files: '*.php',
      options: {
        tabWidth: 4,
        printWidth: 100, // Allow a bit more width for PHP since it uses tabwidth 4
      },
    },
    {
      files: ['*.yaml', '*.neon'],
      options: {
        tabWidth: 4, // Match symfony default
      },
    },
  ],
};
