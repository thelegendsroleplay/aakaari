import { useState, useEffect } from 'react';
import { X } from 'lucide-react';
import { MobileMenuHeader } from './MobileMenuHeader';
import { MobileMenuNav } from './MobileMenuNav';
import { MobileMenuActions } from './MobileMenuActions';
import { MobileMenuFooter } from './MobileMenuFooter';
import './mobile-menu.css';
import './mobile-utils.css';

interface MobileMenuProps {
  isOpen: boolean;
  onClose: () => void;
  currentPage: string;
  onNavigate: (page: string) => void;
  isLoggedIn?: boolean;
  userRole?: 'reseller' | 'admin' | null;
  userName?: string;
  userEmail?: string;
  cartCount?: number;
  onLogout?: () => void;
}

export function MobileMenu({
  isOpen,
  onClose,
  currentPage,
  onNavigate,
  isLoggedIn = false,
  userRole = null,
  userName,
  userEmail,
  cartCount = 0,
  onLogout,
}: MobileMenuProps) {
  const [expandedSection, setExpandedSection] = useState<string | null>(null);

  const handleNavClick = (page: string) => {
    onNavigate(page);
    onClose();
  };

  const toggleSection = (section: string) => {
    setExpandedSection(expandedSection === section ? null : section);
  };

  // Prevent body scroll when menu is open
  useEffect(() => {
    if (isOpen) {
      document.body.classList.add('mobile-menu-open');
    } else {
      document.body.classList.remove('mobile-menu-open');
    }
    
    return () => {
      document.body.classList.remove('mobile-menu-open');
    };
  }, [isOpen]);

  return (
    <>
      {/* Backdrop */}
      <div
        className={`mobile-menu-backdrop ${isOpen ? 'mobile-menu-backdrop--open' : ''}`}
        onClick={onClose}
        aria-hidden="true"
      />

      {/* Menu Panel */}
      <div className={`mobile-menu ${isOpen ? 'mobile-menu--open' : ''}`}>
        <div className="mobile-menu__container">
          {/* Close Button */}
          <button
            onClick={onClose}
            className="mobile-menu__close-btn"
            aria-label="Close menu"
          >
            <X className="h-6 w-6" />
          </button>

          {/* Header Section */}
          <MobileMenuHeader
            isLoggedIn={isLoggedIn}
            userName={userName}
            userEmail={userEmail}
            userRole={userRole}
            onNavigate={handleNavClick}
          />

          {/* Scrollable Content */}
          <div className="mobile-menu__content">
            {/* Quick Actions (for logged-in users) */}
            {isLoggedIn && (
              <MobileMenuActions
                cartCount={cartCount}
                onNavigate={handleNavClick}
                userRole={userRole}
              />
            )}

            {/* Main Navigation */}
            <MobileMenuNav
              currentPage={currentPage}
              onNavigate={handleNavClick}
              isLoggedIn={isLoggedIn}
              userRole={userRole}
              expandedSection={expandedSection}
              toggleSection={toggleSection}
            />

            {/* CTA Section (for non-logged-in users) */}
            {!isLoggedIn && (
              <div className="mobile-menu__cta">
                <button
                  onClick={() => handleNavClick('become-reseller')}
                  className="mobile-menu__cta-btn mobile-menu__cta-btn--primary"
                >
                  Become a Reseller
                </button>
                <button
                  onClick={() => handleNavClick('login')}
                  className="mobile-menu__cta-btn mobile-menu__cta-btn--secondary"
                >
                  Login
                </button>
              </div>
            )}
          </div>

          {/* Footer */}
          <MobileMenuFooter
            isLoggedIn={isLoggedIn}
            onNavigate={handleNavClick}
            onLogout={onLogout}
          />
        </div>
      </div>
    </>
  );
}
