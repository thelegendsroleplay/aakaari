import { ShoppingCart, Package, Wallet, TrendingUp } from 'lucide-react';
import './mobile-menu-actions.css';

interface MobileMenuActionsProps {
  cartCount?: number;
  onNavigate: (page: string) => void;
  userRole?: 'reseller' | 'admin' | null;
}

export function MobileMenuActions({
  cartCount = 0,
  onNavigate,
  userRole,
}: MobileMenuActionsProps) {
  const actions = [
    {
      label: 'Cart',
      value: 'cart',
      icon: ShoppingCart,
      badge: cartCount > 0 ? cartCount : undefined,
      show: true,
    },
    {
      label: 'Orders',
      value: 'track-order',
      icon: Package,
      show: userRole === 'reseller',
    },
    {
      label: 'Wallet',
      value: 'dashboard',
      icon: Wallet,
      show: userRole === 'reseller',
    },
    {
      label: 'Earnings',
      value: 'dashboard',
      icon: TrendingUp,
      show: userRole === 'reseller',
    },
  ];

  const visibleActions = actions.filter((action) => action.show);

  return (
    <div className="mobile-menu-actions">
      <div className="mobile-menu-actions__grid">
        {visibleActions.map((action) => {
          const Icon = action.icon;
          return (
            <button
              key={action.value}
              onClick={() => onNavigate(action.value)}
              className="mobile-menu-actions__item"
            >
              <div className="mobile-menu-actions__icon-wrapper">
                <Icon className="mobile-menu-actions__icon" />
                {action.badge !== undefined && (
                  <span className="mobile-menu-actions__badge">{action.badge}</span>
                )}
              </div>
              <span className="mobile-menu-actions__label">{action.label}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
}
