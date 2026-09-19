export default function Footer() {
  return (
    <footer className="border-t border-white/5 py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* Brand */}
          <div>
            <div className="flex items-center space-x-3 mb-4">
              <div className="w-9 h-9 bg-gradient-to-br from-purple-500 to-blue-500 rounded-lg flex items-center justify-center">
                <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <span className="text-lg font-bold text-white">WP SEO Master</span>
            </div>
            <p className="text-sm text-gray-400 leading-relaxed">
              WordPress için tamamen ücretsiz, açık kaynaklı SEO eklentisi. 
              Schema.org desteği, meta etiketleri, sitemap ve daha fazlası.
            </p>
          </div>

          {/* Technical */}
          <div>
            <h4 className="text-sm font-semibold text-white mb-4">Teknik Gereksinimler</h4>
            <ul className="space-y-2 text-sm text-gray-400">
              <li className="flex items-center">
                <span className="w-1.5 h-1.5 bg-purple-400 rounded-full mr-2" />
                PHP 7.4 veya üzeri
              </li>
              <li className="flex items-center">
                <span className="w-1.5 h-1.5 bg-blue-400 rounded-full mr-2" />
                WordPress 6.0 veya üzeri
              </li>
              <li className="flex items-center">
                <span className="w-1.5 h-1.5 bg-green-400 rounded-full mr-2" />
                WPCS uyumlu
              </li>
              <li className="flex items-center">
                <span className="w-1.5 h-1.5 bg-yellow-400 rounded-full mr-2" />
                GPL-2.0+ Lisans
              </li>
            </ul>
          </div>

          {/* Standards */}
          <div>
            <h4 className="text-sm font-semibold text-white mb-4">Standartlar</h4>
            <ul className="space-y-2 text-sm text-gray-400">
              <li className="flex items-center">
                <span className="w-1.5 h-1.5 bg-purple-400 rounded-full mr-2" />
                WordPress Coding Standards
              </li>
              <li className="flex items-center">
                <span className="w-1.5 h-1.5 bg-blue-400 rounded-full mr-2" />
                PSR-4 Autoloading
              </li>
              <li className="flex items-center">
                <span className="w-1.5 h-1.5 bg-green-400 rounded-full mr-2" />
                Schema.org JSON-LD
              </li>
              <li className="flex items-center">
                <span className="w-1.5 h-1.5 bg-yellow-400 rounded-full mr-2" />
                Translation Ready (i18n)
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom */}
        <div className="mt-12 pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p className="text-xs text-gray-500">
            © 2024 WP SEO Master. GPL-2.0+ lisansı altında dağıtılmaktadır.
          </p>
          <div className="flex items-center space-x-4">
            <span className="text-xs text-gray-600">
              PHP • WordPress • Schema.org • REST API
            </span>
          </div>
        </div>
      </div>
    </footer>
  )
}
