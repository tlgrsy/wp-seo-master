const fileTree = [
  {
    name: 'wp-seo-master/',
    type: 'folder',
    level: 0,
    children: [
      { name: 'wp-seo-master.php', type: 'file', description: 'Ana dosya, bootstrap', icon: '🔌' },
      { name: 'uninstall.php', type: 'file', description: 'Temizlik (silme işlemi)', icon: '🗑️' },
      { name: 'readme.txt', type: 'file', description: 'WordPress.org readme', icon: '📄' },
      {
        name: 'assets/',
        type: 'folder',
        level: 1,
        children: [
          {
            name: 'js/',
            type: 'folder',
            level: 2,
            children: [
              { name: 'admin.js', type: 'file', description: 'Metabox JS (karakter sayacı, tab, media, AJAX)', icon: '⚡' },
            ],
          },
          {
            name: 'css/',
            type: 'folder',
            level: 2,
            children: [
              { name: 'admin.css', type: 'file', description: 'Metabox stilleri (tab, form, SERP preview)', icon: '🎨' },
            ],
          },
        ],
      },
      {
        name: 'includes/',
        type: 'folder',
        level: 1,
        children: [
          { name: 'class-plugin.php', type: 'file', description: 'Singleton, hook yükleyici', icon: '⚙️' },
          { name: 'class-autoloader.php', type: 'file', description: 'PSR-4 autoloader', icon: '📦' },
          { name: 'class-installer.php', type: 'file', description: 'Aktivasyon, migration, DB versiyon', icon: '🔧' },
          { name: 'class-options.php', type: 'file', description: 'get/set/all/update, static cache', icon: '⚡' },
          { name: 'class-i18n.php', type: 'file', description: 'Text domain, .mo/.po yönetimi', icon: '🌐' },
          {
            name: 'Admin/',
            type: 'folder',
            level: 2,
            children: [
              { name: 'class-admin-menu.php', type: 'file', description: 'Admin menü kaydı', icon: '📋' },
              { name: 'class-settings.php', type: 'file', description: 'Ayarlar sayfası', icon: '⚙️' },
              { name: 'class-metabox.php', type: 'file', description: 'SEO Metabox (4 sekme, AJAX, REST API)', icon: '📝' },
              {
                name: 'views/',
                type: 'folder',
                description: 'PHP template dosyaları',
                icon: '👁️',
                level: 3,
                children: [
                  { name: 'metabox.php', type: 'file', description: 'Metabox HTML template', icon: '🖼️' },
                ],
              },
            ],
          },
          {
            name: 'Frontend/',
            type: 'folder',
            level: 2,
            children: [
              { name: 'class-meta-tags.php', type: 'file', description: 'Title, desc, robots, verification', icon: '🏷️' },
              { name: 'class-opengraph.php', type: 'file', description: 'OG tags, article, product (WooCommerce)', icon: '📱' },
              { name: 'class-twitter-cards.php', type: 'file', description: 'Twitter card, site, creator, image', icon: '🐦' },
              { name: 'class-canonical.php', type: 'file', description: 'rel=canonical, prev/next', icon: '🔗' },
              { name: 'class-breadcrumbs.php', type: 'file', description: 'Breadcrumb navigasyon', icon: '🧭' },
              { name: 'class-robots.php', type: 'file', description: 'wp_robots, robots.txt', icon: '🤖' },
            ],
          },
          {
            name: 'Schema/',
            type: 'folder',
            level: 2,
            children: [
              { name: 'class-schema-manager.php', type: 'file', description: '@graph yapısı, WebSite, Organization', icon: '📊' },
              { name: 'class-article-schema.php', type: 'file', description: 'Article/BlogPosting, author, publisher', icon: '📰' },
              { name: 'class-faq-schema.php', type: 'file', description: 'FAQ şeması', icon: '❓' },
              { name: 'class-howto-schema.php', type: 'file', description: 'HowTo şeması', icon: '📖' },
              { name: 'class-product-schema.php', type: 'file', description: 'Product şeması', icon: '🛒' },
              { name: 'class-localbusiness-schema.php', type: 'file', description: 'LocalBusiness şeması', icon: '🏪' },
              { name: 'class-breadcrumb-schema.php', type: 'file', description: 'BreadcrumbList, itemListElement', icon: '🧭' },
            ],
          },
          {
            name: 'Sitemap/',
            type: 'folder',
            level: 2,
            children: [
              { name: 'class-sitemap-generator.php', type: 'file', description: 'XML sitemap, rewrite rules, cache, görsel', icon: '🗺️' },
              { name: 'class-sitemap-index.php', type: 'file', description: 'Sitemap index, alt sitemap listesi', icon: '📑' },
            ],
          },
          {
            name: 'Analyzer/',
            type: 'folder',
            level: 2,
            children: [
              { name: 'class-content-analyzer.php', type: 'file', description: 'İçerik SEO analizi', icon: '🔍' },
            ],
          },
        ],
      },
    ],
  },
]

interface FileItem {
  name: string
  type: string
  description?: string
  icon?: string
  level?: number
  children?: FileItem[]
}

function FileTreeItem({ item, depth = 0 }: { item: FileItem; depth?: number }) {
  const isFolder = item.type === 'folder'

  return (
    <div>
      <div
        className={`flex items-center py-2 px-3 rounded-lg hover:bg-white/5 transition-colors group`}
        style={{ paddingLeft: `${depth * 20 + 12}px` }}
      >
        {isFolder ? (
          <svg className="w-4 h-4 text-yellow-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
          </svg>
        ) : (
          <span className="mr-2 text-sm">{item.icon || '📄'}</span>
        )}
        <span className={`text-sm font-mono ${isFolder ? 'text-yellow-300 font-semibold' : 'text-gray-300'}`}>
          {item.name}
        </span>
        {item.description && (
          <span className="ml-3 text-xs text-gray-500 group-hover:text-gray-400 transition-colors hidden sm:inline">
            — {item.description}
          </span>
        )}
      </div>
      {item.children && (
        <div>
          {item.children.map((child, index) => (
            <FileTreeItem key={index} item={child} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  )
}

export default function FileStructure() {
  return (
    <section id="structure" className="py-24 relative">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="text-center mb-12">
          <span className="inline-block px-3 py-1 text-xs font-medium bg-green-500/10 text-green-400 rounded-full border border-green-500/20 mb-4">
            DOSYA YAPISI
          </span>
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-4">
            Eklenti Dosya Yapısı
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            Modüler ve organize dosya yapısı. Her klasör kendi sorumluluk alanını temsil eder.
          </p>
        </div>

        {/* File Tree */}
        <div className="bg-gray-900/50 border border-white/5 rounded-2xl p-6 overflow-x-auto">
          <div className="flex items-center space-x-2 mb-4 pb-4 border-b border-white/5">
            <div className="w-3 h-3 rounded-full bg-red-500/80" />
            <div className="w-3 h-3 rounded-full bg-yellow-500/80" />
            <div className="w-3 h-3 rounded-full bg-green-500/80" />
            <span className="ml-4 text-xs text-gray-500 font-mono">wp-seo-master/</span>
          </div>
          {fileTree.map((item, index) => (
            <FileTreeItem key={index} item={item} />
          ))}
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-8">
          {[
            { label: 'Toplam Dosya', value: '30+' },
            { label: 'Klasör', value: '10' },
            { label: 'Namespace', value: '6' },
            { label: 'Schema Tipi', value: '6' },
          ].map((stat, i) => (
            <div key={i} className="text-center p-4 bg-white/[0.02] border border-white/5 rounded-xl">
              <div className="text-2xl font-bold text-white">{stat.value}</div>
              <div className="text-xs text-gray-500 mt-1">{stat.label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
