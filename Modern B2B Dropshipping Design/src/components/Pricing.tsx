import { Check } from 'lucide-react';
import { Button } from './ui/button';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';

interface PricingProps {
  onNavigate: (page: string) => void;
}

export function Pricing({ onNavigate }: PricingProps) {
  const plans = [
    {
      name: 'Free',
      price: '₹0',
      period: 'Forever',
      description: 'Perfect for getting started',
      features: [
        'Access to full product catalog',
        'Standard wholesale pricing',
        'Minimum order quantities apply',
        'Standard shipping (3-7 days)',
        'Email support',
        'Dashboard access',
        'Product images download',
        'Commission tracking',
      ],
      notIncluded: [
        'Priority support',
        'Bulk pricing tiers',
        'Dedicated account manager',
      ],
      cta: 'Get Started Free',
      popular: false,
    },
    {
      name: 'Pro',
      price: '₹999',
      period: 'per month',
      description: 'For serious resellers',
      features: [
        'Everything in Free plan',
        'Better wholesale pricing (5-10% off)',
        'Reduced MOQ on select products',
        'Priority shipping (2-4 days)',
        'Priority email & WhatsApp support',
        'Advanced analytics dashboard',
        'Marketing material support',
        'Referral bonuses',
        'Monthly product updates',
      ],
      notIncluded: [
        'Dedicated account manager',
      ],
      cta: 'Start Pro Trial',
      popular: true,
    },
    {
      name: 'Enterprise',
      price: 'Custom',
      period: 'Contact us',
      description: 'For high-volume businesses',
      features: [
        'Everything in Pro plan',
        'Best wholesale pricing (10-15% off)',
        'Custom MOQ arrangements',
        'Express shipping (1-2 days)',
        'Dedicated account manager',
        '24/7 priority support',
        'Custom product sourcing',
        'White-label options',
        'API access',
        'Custom payment terms',
      ],
      notIncluded: [],
      cta: 'Contact Sales',
      popular: false,
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero */}
      <div className="bg-gradient-to-br from-blue-600 to-blue-700 text-white">
        <div className="container mx-auto px-4 py-16 text-center">
          <h1 className="text-4xl md:text-5xl mb-4">Simple, Transparent Pricing</h1>
          <p className="text-xl text-blue-100 max-w-2xl mx-auto">
            Start for free, upgrade when you need more. No hidden fees.
          </p>
        </div>
      </div>

      {/* Pricing Cards */}
      <div className="container mx-auto px-4 py-16">
        <div className="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
          {plans.map((plan, index) => (
            <Card
              key={index}
              className={`relative ${
                plan.popular
                  ? 'border-2 border-blue-600 shadow-xl'
                  : ''
              }`}
            >
              {plan.popular && (
                <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                  <Badge className="bg-blue-600">Most Popular</Badge>
                </div>
              )}
              <CardHeader>
                <CardTitle className="text-center">
                  <div className="mb-4">{plan.name}</div>
                  <div className="text-4xl mb-2">{plan.price}</div>
                  <div className="text-sm text-gray-600">{plan.period}</div>
                  <p className="text-sm text-gray-600 mt-4">{plan.description}</p>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <Button
                  className={`w-full mb-6 ${
                    plan.popular
                      ? 'bg-blue-600 hover:bg-blue-700'
                      : ''
                  }`}
                  variant={plan.popular ? 'default' : 'outline'}
                  onClick={() => onNavigate('become-reseller')}
                >
                  {plan.cta}
                </Button>
                <ul className="space-y-3">
                  {plan.features.map((feature, idx) => (
                    <li key={idx} className="flex items-start gap-2">
                      <Check className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                      <span className="text-sm">{feature}</span>
                    </li>
                  ))}
                  {plan.notIncluded.map((feature, idx) => (
                    <li key={idx} className="flex items-start gap-2 opacity-40">
                      <Check className="h-5 w-5 flex-shrink-0 mt-0.5" />
                      <span className="text-sm line-through">{feature}</span>
                    </li>
                  ))}
                </ul>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>

      {/* Comparison Table */}
      <div className="bg-white py-16">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl mb-4">Feature Comparison</h2>
            <p className="text-gray-600">See what's included in each plan</p>
          </div>
          <div className="max-w-4xl mx-auto overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-4 px-4">Feature</th>
                  <th className="text-center py-4 px-4">Free</th>
                  <th className="text-center py-4 px-4">Pro</th>
                  <th className="text-center py-4 px-4">Enterprise</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                <tr>
                  <td className="py-4 px-4">Product catalog access</td>
                  <td className="text-center py-4 px-4">
                    <Check className="h-5 w-5 text-green-600 mx-auto" />
                  </td>
                  <td className="text-center py-4 px-4">
                    <Check className="h-5 w-5 text-green-600 mx-auto" />
                  </td>
                  <td className="text-center py-4 px-4">
                    <Check className="h-5 w-5 text-green-600 mx-auto" />
                  </td>
                </tr>
                <tr>
                  <td className="py-4 px-4">Wholesale pricing</td>
                  <td className="text-center py-4 px-4">Standard</td>
                  <td className="text-center py-4 px-4">5-10% off</td>
                  <td className="text-center py-4 px-4">10-15% off</td>
                </tr>
                <tr>
                  <td className="py-4 px-4">Shipping time</td>
                  <td className="text-center py-4 px-4">3-7 days</td>
                  <td className="text-center py-4 px-4">2-4 days</td>
                  <td className="text-center py-4 px-4">1-2 days</td>
                </tr>
                <tr>
                  <td className="py-4 px-4">Support</td>
                  <td className="text-center py-4 px-4">Email</td>
                  <td className="text-center py-4 px-4">Priority</td>
                  <td className="text-center py-4 px-4">24/7 Dedicated</td>
                </tr>
                <tr>
                  <td className="py-4 px-4">Account manager</td>
                  <td className="text-center py-4 px-4">-</td>
                  <td className="text-center py-4 px-4">-</td>
                  <td className="text-center py-4 px-4">
                    <Check className="h-5 w-5 text-green-600 mx-auto" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* FAQ */}
      <div className="container mx-auto px-4 py-16">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl mb-4">Pricing FAQs</h2>
        </div>
        <div className="max-w-3xl mx-auto space-y-4">
          <Card>
            <CardContent className="pt-6">
              <h3 className="mb-2">Can I change plans later?</h3>
              <p className="text-gray-600">
                Yes! You can upgrade or downgrade your plan at any time. Changes take effect immediately.
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <h3 className="mb-2">Is there a free trial for Pro plan?</h3>
              <p className="text-gray-600">
                Yes, we offer a 7-day free trial for the Pro plan. No credit card required.
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <h3 className="mb-2">What payment methods do you accept?</h3>
              <p className="text-gray-600">
                We accept UPI, credit/debit cards, net banking, and bank transfers.
              </p>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* CTA */}
      <div className="bg-blue-600 text-white py-16">
        <div className="container mx-auto px-4 text-center">
          <h2 className="text-3xl md:text-4xl mb-4">Still Have Questions?</h2>
          <p className="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Our team is here to help you choose the right plan
          </p>
          <Button
            size="lg"
            onClick={() => onNavigate('contact')}
            className="bg-white text-blue-600 hover:bg-blue-50"
          >
            Contact Sales
          </Button>
        </div>
      </div>
    </div>
  );
}
