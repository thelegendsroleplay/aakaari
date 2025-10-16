import { ArrowRight, Package, TrendingUp, Truck, Download, CheckCircle, Star, Users } from 'lucide-react';
import { Button } from './ui/button';
import { Card, CardContent } from './ui/card';
import { Badge } from './ui/badge';
import { ImageWithFallback } from './figma/ImageWithFallback';

interface HomePageProps {
  onNavigate: (page: string) => void;
}

export function HomePage({ onNavigate }: HomePageProps) {
  const categories = [
    { name: 'Fashion & Apparel', count: '500+', color: 'bg-blue-100 text-blue-700' },
    { name: 'Electronics', count: '300+', color: 'bg-purple-100 text-purple-700' },
    { name: 'Home & Living', count: '400+', color: 'bg-green-100 text-green-700' },
    { name: 'Beauty & Care', count: '250+', color: 'bg-pink-100 text-pink-700' },
    { name: 'Accessories', count: '350+', color: 'bg-orange-100 text-orange-700' },
    { name: 'Sports & Fitness', count: '200+', color: 'bg-red-100 text-red-700' },
  ];

  const testimonials = [
    {
      name: 'Rajesh Kumar',
      role: 'Reseller from Delhi',
      image: 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxoYXBweSUyMGJ1c2luZXNzJTIwb3duZXJ8ZW58MXx8fHwxNzYwNjM2NDQwfDA&ixlib=rb-4.1.0&q=80&w=1080',
      quote: 'Aakaari transformed my business. I started with just ₹5000 and now earning ₹50,000+ monthly!',
      rating: 5,
    },
    {
      name: 'Priya Sharma',
      role: 'Reseller from Bangalore',
      image: 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400',
      quote: 'The best part is zero inventory risk. They handle shipping and I focus on sales. Perfect!',
      rating: 5,
    },
    {
      name: 'Amit Patel',
      role: 'Reseller from Mumbai',
      image: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=400',
      quote: 'Quality products, fast shipping, and great margins. My customers are always happy!',
      rating: 5,
    },
  ];

  const stats = [
    { value: '10K+', label: 'Active Resellers' },
    { value: '50K+', label: 'Products Shipped' },
    { value: '₹2Cr+', label: 'Commissions Paid' },
    { value: '98%', label: 'Satisfaction Rate' },
  ];

  return (
    <div className="min-h-screen">
      {/* Hero Section */}
      <section className="relative bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 text-white">
        <div className="container mx-auto px-4 py-16 md:py-24">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div className="space-y-6">
              <Badge className="bg-blue-500 hover:bg-blue-400">
                🚀 India's Fastest Growing B2B Platform
              </Badge>
              <h1 className="text-4xl md:text-5xl lg:text-6xl">
                Wholesale for Resellers.<br />
                <span className="text-blue-200">Buy Low. Sell High.</span>
              </h1>
              <p className="text-lg text-blue-100">
                Start your dropshipping business with 50-100% profit margins. No inventory, no risk. 
                We handle storage, packing, and shipping — you focus on selling.
              </p>
              <div className="flex flex-col sm:flex-row gap-4">
                <Button 
                  size="lg"
                  onClick={() => onNavigate('become-reseller')}
                  className="bg-white text-blue-600 hover:bg-blue-50"
                >
                  Become a Reseller
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
                <Button 
                  size="lg"
                  variant="outline"
                  onClick={() => onNavigate('products')}
                  className="border-white text-white hover:bg-blue-700"
                >
                  View Catalog
                </Button>
              </div>
              <div className="flex flex-wrap gap-6 pt-4">
                {stats.map((stat, index) => (
                  <div key={index}>
                    <div className="text-2xl">{stat.value}</div>
                    <div className="text-sm text-blue-200">{stat.label}</div>
                  </div>
                ))}
              </div>
            </div>
            <div className="relative hidden lg:block">
              <ImageWithFallback
                src="https://images.unsplash.com/photo-1734366513184-8c44c7527d85?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3aG9sZXNhbGUlMjBkaXN0cmlidXRpb24lMjB3YXJlaG91c2V8ZW58MXx8fHwxNzYwNjM2NDQwfDA&ixlib=rb-4.1.0&q=80&w=1080"
                alt="Warehouse"
                className="rounded-lg shadow-2xl"
              />
            </div>
          </div>
        </div>
      </section>

      {/* How It Works */}
      <section className="py-16 md:py-24 bg-gray-50">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl mb-4">How It Works</h2>
            <p className="text-gray-600 max-w-2xl mx-auto">
              Start your dropshipping journey in 4 simple steps
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {[
              {
                icon: Users,
                title: 'Register & Get Verified',
                description: 'Sign up and complete KYC. Get approved within 24 hours.',
                step: '01',
              },
              {
                icon: Package,
                title: 'Browse & Order',
                description: 'Choose from 1500+ products at wholesale prices with minimum order quantity.',
                step: '02',
              },
              {
                icon: TrendingUp,
                title: 'Sell & Earn',
                description: 'Share product links, sell to customers. We ship directly to them.',
                step: '03',
              },
              {
                icon: Truck,
                title: 'Track & Grow',
                description: 'Real-time tracking. Earn commissions. Withdraw to your bank instantly.',
                step: '04',
              },
            ].map((step, index) => (
              <Card key={index} className="relative overflow-hidden">
                <CardContent className="pt-6">
                  <div className="absolute top-0 right-0 text-6xl opacity-5">
                    {step.step}
                  </div>
                  <div className="relative">
                    <div className="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center mb-4">
                      <step.icon className="h-6 w-6 text-blue-600" />
                    </div>
                    <h3 className="mb-2">{step.title}</h3>
                    <p className="text-sm text-gray-600">{step.description}</p>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Categories */}
      <section className="py-16 md:py-24">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl mb-4">Product Categories</h2>
            <p className="text-gray-600">
              Explore our wide range of wholesale products
            </p>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {categories.map((category, index) => (
              <button
                key={index}
                onClick={() => onNavigate('products')}
                className="p-6 rounded-lg border-2 border-gray-200 hover:border-blue-500 hover:shadow-lg transition-all text-center group"
              >
                <div className={`inline-flex px-3 py-1 rounded-full text-sm mb-2 ${category.color}`}>
                  {category.count}
                </div>
                <div className="text-sm group-hover:text-blue-600 transition-colors">
                  {category.name}
                </div>
              </button>
            ))}
          </div>
        </div>
      </section>

      {/* Featured Products */}
      <section className="py-16 md:py-24 bg-gray-50">
        <div className="container mx-auto px-4">
          <div className="flex justify-between items-center mb-12">
            <div>
              <h2 className="text-3xl md:text-4xl mb-2">Featured Products</h2>
              <p className="text-gray-600">High-margin bestsellers</p>
            </div>
            <Button onClick={() => onNavigate('products')} variant="outline">
              View All
              <ArrowRight className="ml-2 h-4 w-4" />
            </Button>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {[1, 2, 3, 4].map((i) => (
              <Card key={i} className="group cursor-pointer hover:shadow-lg transition-shadow">
                <CardContent className="p-0">
                  <div className="relative overflow-hidden">
                    <ImageWithFallback
                      src="https://images.unsplash.com/photo-1632337950445-ba446cb0e26f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjbG90aGluZyUyMHByb2R1Y3RzfGVufDF8fHx8MTc2MDYzNjQ0MXww&ixlib=rb-4.1.0&q=80&w=1080"
                      alt={`Product ${i}`}
                      className="w-full h-48 object-cover group-hover:scale-105 transition-transform"
                    />
                    <Badge className="absolute top-2 right-2 bg-green-500">
                      100% Margin
                    </Badge>
                  </div>
                  <div className="p-4">
                    <h3 className="mb-2">Premium Cotton T-Shirt</h3>
                    <div className="flex justify-between items-center mb-2">
                      <div>
                        <div className="text-sm text-gray-500">Wholesale</div>
                        <div className="text-blue-600">₹120</div>
                      </div>
                      <div>
                        <div className="text-sm text-gray-500">Retail</div>
                        <div className="text-green-600">₹299</div>
                      </div>
                    </div>
                    <div className="text-xs text-gray-500">MOQ: 10 pcs</div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonials */}
      <section className="py-16 md:py-24">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl mb-4">Success Stories</h2>
            <p className="text-gray-600">
              See what our resellers say about Aakaari
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            {testimonials.map((testimonial, index) => (
              <Card key={index}>
                <CardContent className="pt-6">
                  <div className="flex mb-4">
                    {[...Array(testimonial.rating)].map((_, i) => (
                      <Star key={i} className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                    ))}
                  </div>
                  <p className="text-gray-600 mb-6 italic">"{testimonial.quote}"</p>
                  <div className="flex items-center gap-3">
                    <ImageWithFallback
                      src={testimonial.image}
                      alt={testimonial.name}
                      className="h-12 w-12 rounded-full object-cover"
                    />
                    <div>
                      <div>{testimonial.name}</div>
                      <div className="text-sm text-gray-500">{testimonial.role}</div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-16 md:py-24 bg-blue-600 text-white">
        <div className="container mx-auto px-4 text-center">
          <h2 className="text-3xl md:text-4xl mb-4">
            Ready to Start Your Journey?
          </h2>
          <p className="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Join 10,000+ resellers who are building successful businesses with Aakaari
          </p>
          <Button 
            size="lg"
            onClick={() => onNavigate('become-reseller')}
            className="bg-white text-blue-600 hover:bg-blue-50"
          >
            Become a Reseller Today
            <ArrowRight className="ml-2 h-5 w-5" />
          </Button>
        </div>
      </section>
    </div>
  );
}
