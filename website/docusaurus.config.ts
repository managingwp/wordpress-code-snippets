import {themes as prismThemes} from 'prism-react-renderer';
import type {Config} from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';

const repo = 'managingwp/wordpress-code-snippets';

const config: Config = {
  title: 'WordPress Code Snippets',
  tagline: 'A collection of reusable WordPress PHP snippets.',
  favicon: 'img/favicon.ico',

  url: 'https://managingwp.github.io',
  baseUrl: '/wordpress-code-snippets/',

  organizationName: 'managingwp',
  projectName: 'wordpress-code-snippets',

  trailingSlash: false,
  onBrokenLinks: 'throw',
  onBrokenAnchors: 'throw',
  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'throw',
    },
  },

  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  presets: [
    [
      'classic',
      {
        docs: {
          routeBasePath: '/',
          sidebarPath: './sidebars.ts',
          editUrl: `https://github.com/${repo}/edit/main/website/`,
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      } satisfies Preset.Options,
    ],
  ],

  themes: [
    [
      '@easyops-cn/docusaurus-search-local',
      {
        hashed: 'filename',
        language: ['en'],
        indexBlog: false,
        docsRouteBasePath: '/',
      },
    ],
  ],

  themeConfig: {
    colorMode: {
      defaultMode: 'light',
      disableSwitch: false,
      respectPrefersColorScheme: true,
    },
    navbar: {
      title: 'WordPress Code Snippets',
      items: [
        {
          type: 'docSidebar',
          sidebarId: 'docs',
          position: 'left',
          label: 'Snippets',
        },
        {
          href: `https://github.com/${repo}`,
          label: 'GitHub',
          position: 'right',
        },
      ],
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'Docs',
          items: [
            {label: 'Install a snippet', to: '/getting-started/install-a-snippet'},
            {label: 'Add a snippet', to: '/contributing/add-a-snippet'},
          ],
        },
        {
          title: 'Repository',
          items: [
            {label: 'GitHub', href: `https://github.com/${repo}`},
            {label: 'Changelog', to: '/history/changelog'},
          ],
        },
      ],
      copyright: `Built with Docusaurus.`,
    },
    prism: {
      theme: prismThemes.github,
      darkTheme: prismThemes.dracula,
    },
  } satisfies Preset.ThemeConfig,
};

export default config;
