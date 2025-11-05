import { ShoppingBag, User, Crown } from 'lucide-react';
import './mobile-menu-header.css';

interface MobileMenuHeaderProps {
  isLoggedIn?: boolean;
  userName?: string;
  userRole?: 'reseller' | 'admin' | null;
  onNavigate: (page: string) => void;
}

export function MobileMenuHeader({
  isLoggedIn = false,
  userName,
  userRole,
  onNavigate,
}: MobileMenuHeaderProps) {
  return (
    <div className="mobile-menu-header">
      {/* Logo */}
      <button
        onClick={() => onNavigate('home')}
        className="mobile-menu-header__logo"
      >
        <ShoppingBag className="mobile-menu-header__logo-icon" />
        <span className="mobile-menu-header__logo-text">Aakaari</span>
      </button>

      {/* User Info (if logged in) */}
      {isLoggedIn && (
        <button
          onClick={() => onNavigate('dashboard')}
          className="mobile-menu-header__user"
        >
          <div className="mobile-menu-header__avatar">
            {userRole === 'admin' ? (
              <Crown className="h-4 w-4" />
            ) : (
              <User className="h-4 w-4" />
            )}
          </div>
          <div className="mobile-menu-header__user-info">
            <span className="mobile-menu-header__user-name">
              {userName || 'User'}
            </span>
            <span className="mobile-menu-header__user-role">
              {userRole === 'admin' ? 'Admin' : 'Reseller'}
            </span>
          </div>
        </button>
      )}
    </div>
  );
}
