import type {SidebarsConfig} from '@docusaurus/plugin-content-docs';

// Only the pages owned by hand are listed here. The snippet categories below the
// marker are written by ./generate-readme.sh from the snippet headers - do not
// edit that block by hand.
const sidebars: SidebarsConfig = {
  docs: [
    {type: 'doc', id: 'index', label: 'Overview'},
    {type: 'doc', id: 'getting-started/install-a-snippet', label: 'Install a snippet'},
    {type: 'doc', id: 'reference/snippet-headers', label: 'Snippet headers'},
    {type: 'doc', id: 'contributing/add-a-snippet', label: 'Add a snippet'},
    {type: 'doc', id: 'history/changelog', label: 'Changelog'},
    // BEGIN GENERATED: snippets
    // END GENERATED: snippets
  ],
};

export default sidebars;
