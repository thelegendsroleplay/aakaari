import { CheckCircle, UserPlus, Package, Share2, TrendingUp } from 'lucide-react';
import { Button } from './ui/button';
import { Card, CardContent } from './ui/card';
import { Badge } from './ui/badge';

interface HowItWorksProps {
  onNavigate: (page: string) => void;
}

export function HowItWorks({ onNavigate }: HowItWorksProps) {
  const steps = [
    {
      icon: UserPlus,
      title: 'Register & Get Verified',
      description: 'Sign up and complete your KYC verification. Our team reviews and approves your application within 24 hours.',
      details: [
        'Fill out the application form',
        'Upload required KYC documents',
        'Get approved by our team',
        'Receive login credentials',
      ],
    },
    {
      icon: Package,
      title: 'Browse & Order Products',
      description: 'Access 1500+ wholesale products across multiple categories. Place orders individually or in bulk using CSV upload.',
      details: [
        'Browse our extensive catalog',
        'Check wholesale prices and margins',
        'Place orders with minimum quantity',
        'Use bulk CSV upload for large orders',
      ],
    },
    {
      icon: Share2,
      title: 'Share & Sell',
      description: 'Share product links with your customers on WhatsApp, social media, or your own website. We handle everything else.',
      details: [
        'Generate personalized product links',
        'Download product images and catalogs',
        'Share with your customer network',
        'Set your own selling prices',
      ],
    },
    {
      icon: TrendingUp,
      title: 'Track & Earn',
      description: 'Track orders in real-time, earn commissions, and withdraw directly to your bank account anytime.',
      details: [
        'Real-time order tracking',
        'Automatic commission credits',
        'Instant withdrawal to bank',
        'Detailed earnings reports',
      ],
    },
  ];

  const faqs = [
    {
      question: 'What is the minimum investment required?',
      answer: 'There is no fixed minimum investment. Each product has its own minimum order quantity (MOQ), typically ranging from 5-20 pieces. You can start with as low as ₹500-1000.',
    },
    {
      question: 'How do I receive payments from customers?',
      answer: 'You collect payments directly from your customers using your preferred method (UPI, bank transfer, COD, etc.). The customer payment goes to you, and you pay us the wholesale price.',
    },
    {
      question: 'Who handles shipping?',
      answer: 'We handle all shipping and logistics. Once you place an order, we ship directly to your customer with tracking details provided to both you and your customer.',
    },
    {
      question: 'Can I return products?',
      answer: 'Yes, we have a clear return and exchange policy. Defective or damaged products can be returned within 7 days. Quality issues are handled with full refund or replacement.',
    },
    {
      question: 'How long does shipping take?',
      answer: 'Standard delivery takes 3-7 business days depending on the location. Express shipping options are also available for faster delivery.',
    },
    {
      question: 'Do I need a GST number?',
      answer: 'GST registration is optional. However, if you have a GSTIN, you can avail input tax credit and may get better pricing on certain products.',
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero */}
      <div className="bg-gradient-to-br from-blue-600 to-blue-700 text-white">
        <div className="container mx-auto px-4 py-16 text-center">
          <Badge className="bg-blue-500 hover:bg-blue-400 mb-4">
            Simple 4-Step Process
          </Badge>
          <h1 className="text-4xl md:text-5xl mb-4">How It Works</h1>
          <p className="text-xl text-blue-100 max-w-2xl mx-auto">
            Start your dropshipping business in 4 simple steps and start earning within days
          </p>
        </div>
      </div>

      {/* Steps */}
      <div className="container mx-auto px-4 py-16">
        <div className="space-y-16">
          {steps.map((step, index) => (
            <div key={index} className="relative">
              <div className="grid lg:grid-cols-2 gap-12 items-center">
                <div className={index % 2 === 1 ? 'lg:order-2' : ''}>
                  <div className="flex items-center gap-4 mb-6">
                    <div className="relative">
                      <div className="h-16 w-16 rounded-full bg-blue-600 text-white flex items-center justify-center text-2xl">
                        {index + 1}
                      </div>
                      {index < steps.length - 1 && (
                        <div className="absolute top-full left-1/2 -translate-x-1/2 w-0.5 h-16 bg-blue-200 hidden lg:block" />
                      )}
                    </div>
                    <div className="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                      <step.icon className="h-6 w-6 text-blue-600" />
                    </div>
                  </div>
                  <h2 className="text-3xl mb-4">{step.title}</h2>
                  <p className="text-gray-600 text-lg mb-6">{step.description}</p>
                  <ul className="space-y-3">
                    {step.details.map((detail, idx) => (
                      <li key={idx} className="flex items-start gap-3">
                        <CheckCircle className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                        <span>{detail}</span>
                      </li>
                    ))}
                  </ul>
                </div>
                <Card className={index % 2 === 1 ? 'lg:order-1' : ''}>
                  <CardContent className="p-8">
                    <div className="aspect-video bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg flex items-center justify-center">
                      <step.icon className="h-24 w-24 text-blue-600 opacity-50" />
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Video Section */}
      <div className="bg-white py-16">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl mb-4">See It In Action</h2>
            <p className="text-gray-600 max-w-2xl mx-auto">
              Watch how successful resellers are using Aakaari to build their business
            </p>
          </div>
          <div className="max-w-4xl mx-auto">
            <div className="aspect-video bg-gray-900 rounded-lg flex items-center justify-center">
              <Button className="bg-white text-gray-900 hover:bg-gray-100">
                <svg className="h-6 w-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
                Watch Demo Video
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* FAQs */}
      <div className="container mx-auto px-4 py-16">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl mb-4">Frequently Asked Questions</h2>
          <p className="text-gray-600">
            Got questions? We've got answers.
          </p>
        </div>
        <div className="max-w-3xl mx-auto space-y-4">
          {faqs.map((faq, index) => (
            <Card key={index}>
              <CardContent className="pt-6">
                <h3 className="mb-3">{faq.question}</h3>
                <p className="text-gray-600">{faq.answer}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>

      {/* CTA */}
      <div className="bg-blue-600 text-white py-16">
        <div className="container mx-auto px-4 text-center">
          <h2 className="text-3xl md:text-4xl mb-4">Ready to Get Started?</h2>
          <p className="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Join thousands of resellers who are earning with Aakaari
          </p>
          <Button
            size="lg"
            onClick={() => onNavigate('become-reseller')}
            className="bg-white text-blue-600 hover:bg-blue-50"
          >
            Become a Reseller Today
          </Button>
        </div>
      </div>
    </div>
  );
}
