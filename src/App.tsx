import { useState } from 'react'
import Header from './components/Header'
import Hero from './components/Hero'
import Features from './components/Features'
import FileStructure from './components/FileStructure'
import CodeViewer from './components/CodeViewer'
import Architecture from './components/Architecture'
import Footer from './components/Footer'

function App() {
  const [activeSection, setActiveSection] = useState('hero')

  return (
    <div className="min-h-screen bg-gray-950 text-white">
      <Header activeSection={activeSection} setActiveSection={setActiveSection} />
      <main>
        <Hero />
        <Features />
        <Architecture />
        <FileStructure />
        <CodeViewer />
      </main>
      <Footer />
    </div>
  )
}

export default App
