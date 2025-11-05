import { LogOut, HelpCircle, Settings, FileText } from 'lucide-react';
import './mobile-menu-footer.css';

interface MobileMenuFooterProps {
  isLoggedIn?: boolean;
  onNavigate: (page: string) => void;
  onLogout?: () => void;
}

export function MobileMenuFooter({ isLoggedIn = false, onNavigate, onLogout }: MobileMenuFooterProps) {
  const handleLogout = () => {
    if (onLogout) {
      onLogout();
    }
    onNavigate('home');
  };

  return (
    <div className="mobile-menu-footer">
      {/* Quick Links */}
      <div className="mobile-menu-footer__links">
        <button
          onClick={() => onNavigate('contact')}
          className="mobile-menu-footer__link"
        >
          <HelpCircle className="mobile-menu-footer__icon" />
          <span>Help & Support</span>
        </button>
        {isLoggedIn && (
          <button
            onClick={() => onNavigate('dashboard')}
            className="mobile-menu-footer__link"
          >
            <Settings className="mobile-menu-footer__icon" />
            <span>Settings</span>
          </button>
        )}
        <button
          onClick={() => onNavigate('how-it-works')}
          className="mobile-menu-footer__link"
        >
          <FileText className="mobile-menu-footer__icon" />
          <span>Terms & Privacy</span>
        </button>
      </div>

      {/* Logout Button */}
      {isLoggedIn && (
        <button onClick={handleLogout} className="mobile-menu-footer__logout">
          <LogOut className="mobile-menu-footer__icon" />
          <span>Logout</span>
        </button>
      )}

      {/* Version Info */}
      <div className="mobile-menu-footer__info">
        <p>Aakaari Platform v1.0.0</p>
        <p>© 2025 All rights reserved</p>
      </div>
    </div>
  );
}
