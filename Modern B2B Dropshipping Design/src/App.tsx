import { useState } from 'react';
import { Header } from './components/Header';
import { Footer } from './components/Footer';
import { HomePage } from './components/HomePage';
import { ProductCatalog } from './components/ProductCatalog';
import { ProductDetail } from './components/ProductDetail';
import { BecomeReseller } from './components/BecomeReseller';
import { ResellerDashboard } from './components/ResellerDashboard';
import { Login } from './components/Login';
import { HowItWorks } from './components/HowItWorks';
import { Pricing } from './components/Pricing';
import { Contact } from './components/Contact';
import { Toaster } from './components/ui/sonner';

export default function App() {
  const [currentPage, setCurrentPage] = useState('home');
  const [isLoggedIn, setIsLoggedIn] = useState(false);

  const handleNavigate = (page: string) => {
    setCurrentPage(page);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleLogin = () => {
    setIsLoggedIn(true);
  };

  const renderPage = () => {
    switch (currentPage) {
      case 'home':
        return <HomePage onNavigate={handleNavigate} />;
      case 'products':
        return <ProductCatalog onNavigate={handleNavigate} isReseller={isLoggedIn} />;
      case 'product-detail':
        return <ProductDetail onNavigate={handleNavigate} isReseller={isLoggedIn} />;
      case 'become-reseller':
        return <BecomeReseller onNavigate={handleNavigate} />;
      case 'dashboard':
        return isLoggedIn ? (
          <ResellerDashboard onNavigate={handleNavigate} />
        ) : (
          <Login onNavigate={handleNavigate} onLogin={handleLogin} />
        );
      case 'login':
        return <Login onNavigate={handleNavigate} onLogin={handleLogin} />;
      case 'how-it-works':
        return <HowItWorks onNavigate={handleNavigate} />;
      case 'pricing':
        return <Pricing onNavigate={handleNavigate} />;
      case 'contact':
        return <Contact onNavigate={handleNavigate} />;
      default:
        return <HomePage onNavigate={handleNavigate} />;
    }
  };

  return (
    <div className="min-h-screen flex flex-col">
      {currentPage !== 'login' && (
        <Header
          currentPage={currentPage}
          onNavigate={handleNavigate}
          isLoggedIn={isLoggedIn}
        />
      )}
      <main className="flex-1">
        {renderPage()}
      </main>
      {currentPage !== 'login' && <Footer onNavigate={handleNavigate} />}
      <Toaster />
    </div>
  );
}
