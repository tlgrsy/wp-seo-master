export default function Architecture() {
  const modules = [
    {
      name: 'Core',
      color: 'border-purple-500/50 bg-purple-500/5',
      items: ['Class_Plugin (Singleton)', 'Class_Autoloader', 'Class_Options', 'Class_Installer', 'Class_I18n'],
    },
    {
      name: 'Admin',
      color: 'border-blue-500/50 bg-blue-500/5',
      items: ['Class_Admin_Menu', 'Class_Settings', 'Class_Metabox', 'Views (Templates)'],
    },
    {
      name: 'Frontend',
      color: 'border-green-500/50 bg-green-500/5',
      items: ['Class_Meta_Tags', 'Class_Opengraph', 'Class_Twitter_Cards', 'Class_Canonical', 'Class_Breadcrumbs', 'Class_Robots'],
    },
    {
      name: 'Schema',
      color: 'border-yellow-500/50 bg-yellow-500/5',
      items: ['Class_Schema_Manager', 'Class_Article_Schema', 'Class_FAQ_Schema', 'Class_Howto_Schema', 'Class_Product_Schema', 'Class_Localbusiness_Schema', 'Class_Breadcrumb_Schema'],
    },
    {
      name: 'Sitemap',
      color: 'border-orange-500/50 bg-orange-500/5',
      items: ['Class_Sitemap_Generator', 'Class_Sitemap_Index'],
    },
    {
      name: 'Analyzer',
      color: 'border-pink-500/50 bg-pink-500/5',
      items: ['Class_Content_Analyzer'],
    },
  ]

  return (
    <section id="architecture" className="py-24 relative">
      {/* Background */}
      <div className="absolute inset-0 bg-gradient-to-b from-transparent via-purple-500/5 to-transparent" />

      <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Section Header */}
        <div className="text-center mb-16">
          <span className="inline-block px-3 py-1 text-xs font-medium bg-blue-500/10 text-blue-400 rounded-full border border-blue-500/20 mb-4">
            MİMARİ
          </span>
          <h2 className="text-3xl sm:text-4xl font-bold text-white mb-4">
            Modüler Mimari Yapısı
          </h2>
          <p className="text-gray-400 max-w-2xl mx-auto">
            Her modül bağımsız çalışır, test edilebilir ve bakımı kolaydır.
            Namespace tabanlı autoloader ile otomatik sınıf yükleme.
          </p>
        </div>

        {/* Architecture Diagram */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {modules.map((module, index) => (
            <div
              key={index}
              className={`p-6 rounded-2xl border ${module.color} backdrop-blur-sm`}
            >
              <h3 className="text-lg font-bold text-white mb-4 flex items-center">
                <span className="w-3 h-3 rounded-full bg-current mr-2 opacity-60" />
                {module.name}
              </h3>
              <ul className="space-y-2">
                {module.items.map((item, i) => (
                  <li key={i} className="flex items-center text-sm text-gray-300">
                    <svg className="w-4 h-4 text-gray-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                    <code className="text-xs bg-black/20 px-2 py-0.5 rounded">{item}</code>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* Data Flow */}
        <div className="mt-16 p-8 bg-white/[0.02] border border-white/5 rounded-2xl">
          <h3 className="text-xl font-bold text-white mb-6 text-center">Veri Akışı</h3>
          <div className="flex flex-col md:flex-row items-center justify-center gap-4 text-sm">
            <div className="px-4 py-3 bg-purple-500/10 border border-purple-500/30 rounded-xl text-purple-300">
              WordPress Core
            </div>
            <svg className="w-6 h-6 text-gray-500 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
            <div className="px-4 py-3 bg-blue-500/10 border border-blue-500/30 rounded-xl text-blue-300">
              Class_Plugin
            </div>
            <svg className="w-6 h-6 text-gray-500 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
            <div className="px-4 py-3 bg-green-500/10 border border-green-500/30 rounded-xl text-green-300">
              Modüller
            </div>
            <svg className="w-6 h-6 text-gray-500 rotate-90 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
            <div className="px-4 py-3 bg-yellow-500/10 border border-yellow-500/30 rounded-xl text-yellow-300">
              HTML Output
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
