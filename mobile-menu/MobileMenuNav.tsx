import {
  Home,
  Package,
  Palette,
  Info,
  DollarSign,
  Mail,
  ChevronDown,
  LayoutDashboard,
  ShoppingCart,
  PackageSearch,
  TrendingUp,
  Settings,
  Users,
} from 'lucide-react';
import './mobile-menu-nav.css';

interface MobileMenuNavProps {
  currentPage: string;
  onNavigate: (page: string) => void;
  isLoggedIn?: boolean;
  userRole?: 'reseller' | 'admin' | null;
  expandedSection: string | null;
  toggleSection: (section: string) => void;
}

export function MobileMenuNav({
  currentPage,
  onNavigate,
  isLoggedIn = false,
  userRole,
  expandedSection,
  toggleSection,
}: MobileMenuNavProps) {
  const mainNavItems = [
    { label: 'Home', value: 'home', icon: Home },
    { label: 'Products', value: 'products', icon: Package },
    { label: 'Custom Design', value: 'custom-products', icon: Palette },
    { label: 'How It Works', value: 'how-it-works', icon: Info },
    { label: 'Pricing', value: 'pricing', icon: DollarSign },
    { label: 'Contact', value: 'contact', icon: Mail },
  ];

  const resellerMenuItems = [
    { label: 'Dashboard', value: 'dashboard', icon: LayoutDashboard },
    { label: 'My Orders', value: 'track-order', icon: PackageSearch },
    { label: 'Analytics', value: 'dashboard', icon: TrendingUp },
  ];

  const adminMenuItems = [
    { label: 'Admin Dashboard', value: 'admin-dashboard', icon: LayoutDashboard },
    { label: 'Manage Products', value: 'admin-dashboard', icon: Package },
    { label: 'User Management', value: 'admin-dashboard', icon: Users },
    { label: 'Settings', value: 'admin-dashboard', icon: Settings },
  ];

  return (
    <nav className="mobile-menu-nav">
      {/* Main Navigation */}
      <div className="mobile-menu-nav__section">
        <div className="mobile-menu-nav__list">
          {mainNavItems.map((item) => {
            const Icon = item.icon;
            return (
              <button
                key={item.value}
                onClick={() => onNavigate(item.value)}
                className={`mobile-menu-nav__item ${
                  currentPage === item.value ? 'mobile-menu-nav__item--active' : ''
                }`}
              >
                <Icon className="mobile-menu-nav__icon" />
                <span className="mobile-menu-nav__label">{item.label}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Reseller Section */}
      {isLoggedIn && userRole === 'reseller' && (
        <div className="mobile-menu-nav__section">
          <button
            className="mobile-menu-nav__section-header"
            onClick={() => toggleSection('reseller')}
          >
            <span>Reseller Tools</span>
            <ChevronDown
              className={`mobile-menu-nav__chevron ${
                expandedSection === 'reseller' ? 'mobile-menu-nav__chevron--expanded' : ''
              }`}
            />
          </button>
          <div
            className={`mobile-menu-nav__collapsible ${
              expandedSection === 'reseller' ? 'mobile-menu-nav__collapsible--expanded' : ''
            }`}
          >
            {resellerMenuItems.map((item) => {
              const Icon = item.icon;
              return (
                <button
                  key={item.value}
                  onClick={() => onNavigate(item.value)}
                  className="mobile-menu-nav__subitem"
                >
                  <Icon className="mobile-menu-nav__icon mobile-menu-nav__icon--small" />
                  <span>{item.label}</span>
                </button>
              );
            })}
          </div>
        </div>
      )}

      {/* Admin Section */}
      {isLoggedIn && userRole === 'admin' && (
        <div className="mobile-menu-nav__section">
          <button
            className="mobile-menu-nav__section-header"
            onClick={() => toggleSection('admin')}
          >
            <span>Admin Tools</span>
            <ChevronDown
              className={`mobile-menu-nav__chevron ${
                expandedSection === 'admin' ? 'mobile-menu-nav__chevron--expanded' : ''
              }`}
            />
          </button>
          <div
            className={`mobile-menu-nav__collapsible ${
              expandedSection === 'admin' ? 'mobile-menu-nav__collapsible--expanded' : ''
            }`}
          >
            {adminMenuItems.map((item) => {
              const Icon = item.icon;
              return (
                <button
                  key={item.value}
                  onClick={() => onNavigate(item.value)}
                  className="mobile-menu-nav__subitem"
                >
                  <Icon className="mobile-menu-nav__icon mobile-menu-nav__icon--small" />
                  <span>{item.label}</span>
                </button>
              );
            })}
          </div>
        </div>
      )}
    </nav>
  );
}
