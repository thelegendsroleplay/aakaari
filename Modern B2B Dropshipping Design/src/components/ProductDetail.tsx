import { useState } from 'react';
import { Star, Truck, Shield, Clock, Download, Share2, Minus, Plus } from 'lucide-react';
import { Button } from './ui/button';
import { Card, CardContent } from './ui/card';
import { Badge } from './ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { ImageWithFallback } from './figma/ImageWithFallback';

interface ProductDetailProps {
  onNavigate: (page: string) => void;
  isReseller?: boolean;
}

export function ProductDetail({ onNavigate, isReseller = false }: ProductDetailProps) {
  const [quantity, setQuantity] = useState(10);
  const [selectedImage, setSelectedImage] = useState(0);

  const product = {
    id: '1',
    name: 'Premium Cotton T-Shirt',
    sku: 'AAK-TSH-001',
    category: 'Fashion & Apparel',
    wholesalePrice: 120,
    retailPrice: 299,
    moq: 10,
    margin: 149,
    stock: 500,
    images: [
      'https://images.unsplash.com/photo-1632337950445-ba446cb0e26f?w=800',
      'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800',
      'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=800',
    ],
    description: 'High-quality premium cotton t-shirt perfect for casual wear. Made from 100% organic cotton with superior breathability and comfort. Available in multiple colors and sizes.',
    features: [
      '100% Premium Cotton',
      'Pre-shrunk fabric',
      'Breathable and comfortable',
      'Available in S, M, L, XL, XXL',
      'Multiple color options',
      'Machine washable',
    ],
    specifications: {
      'Material': '100% Cotton',
      'GSM': '180',
      'Fit': 'Regular',
      'Neck': 'Round Neck',
      'Sleeve': 'Half Sleeve',
      'Pattern': 'Solid',
    },
  };

  const calculateProfit = () => {
    const profit = (product.retailPrice - product.wholesalePrice) * quantity;
    return profit;
  };

  const calculateMargin = () => {
    return Math.round(((product.retailPrice - product.wholesalePrice) / product.wholesalePrice) * 100);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="container mx-auto px-4 py-8">
        <div className="grid lg:grid-cols-2 gap-8 mb-8">
          {/* Image Gallery */}
          <div>
            <div className="sticky top-4">
              <div className="mb-4 rounded-lg overflow-hidden border-2 border-gray-200">
                <ImageWithFallback
                  src={product.images[selectedImage]}
                  alt={product.name}
                  className="w-full h-[500px] object-cover"
                />
              </div>
              <div className="grid grid-cols-3 gap-4">
                {product.images.map((image, index) => (
                  <button
                    key={index}
                    onClick={() => setSelectedImage(index)}
                    className={`rounded-lg overflow-hidden border-2 transition-all ${
                      selectedImage === index ? 'border-blue-600' : 'border-gray-200'
                    }`}
                  >
                    <ImageWithFallback
                      src={image}
                      alt={`${product.name} ${index + 1}`}
                      className="w-full h-24 object-cover"
                    />
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Product Info */}
          <div className="space-y-6">
            <div>
              <div className="flex items-center gap-2 mb-2">
                <Badge>Best Seller</Badge>
                <Badge variant="outline">{product.category}</Badge>
              </div>
              <h1 className="text-3xl mb-2">{product.name}</h1>
              <p className="text-gray-600 mb-4">SKU: {product.sku}</p>
              <div className="flex items-center gap-4 mb-4">
                <div className="flex items-center gap-1">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} className="h-5 w-5 fill-yellow-400 text-yellow-400" />
                  ))}
                </div>
                <span className="text-gray-600">(248 reviews)</span>
              </div>
            </div>

            {isReseller ? (
              <>
                {/* Pricing Card */}
                <Card>
                  <CardContent className="pt-6">
                    <div className="grid grid-cols-2 gap-4 mb-4">
                      <div>
                        <div className="text-sm text-gray-600 mb-1">Wholesale Price</div>
                        <div className="text-2xl text-blue-600">₹{product.wholesalePrice}</div>
                        <div className="text-xs text-gray-500">per piece</div>
                      </div>
                      <div>
                        <div className="text-sm text-gray-600 mb-1">Suggested Retail</div>
                        <div className="text-2xl text-green-600">₹{product.retailPrice}</div>
                        <div className="text-xs text-gray-500">per piece</div>
                      </div>
                    </div>
                    <div className="bg-green-50 text-green-700 p-3 rounded-lg">
                      <div className="flex items-center justify-between">
                        <span>Potential Margin</span>
                        <span className="text-xl">{calculateMargin()}%</span>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                {/* Margin Calculator */}
                <Card>
                  <CardContent className="pt-6">
                    <h3 className="mb-4">Margin Calculator</h3>
                    <div className="space-y-4">
                      <div>
                        <Label htmlFor="quantity">Quantity</Label>
                        <div className="flex items-center gap-3 mt-2">
                          <Button
                            variant="outline"
                            size="icon"
                            onClick={() => setQuantity(Math.max(product.moq, quantity - 1))}
                          >
                            <Minus className="h-4 w-4" />
                          </Button>
                          <Input
                            id="quantity"
                            type="number"
                            value={quantity}
                            onChange={(e) => setQuantity(Math.max(product.moq, parseInt(e.target.value) || product.moq))}
                            className="text-center"
                            min={product.moq}
                          />
                          <Button
                            variant="outline"
                            size="icon"
                            onClick={() => setQuantity(quantity + 1)}
                          >
                            <Plus className="h-4 w-4" />
                          </Button>
                        </div>
                        <p className="text-xs text-gray-500 mt-1">Minimum order: {product.moq} pieces</p>
                      </div>

                      <div className="bg-gray-50 p-4 rounded-lg space-y-2">
                        <div className="flex justify-between">
                          <span className="text-gray-600">Your Investment:</span>
                          <span>₹{(product.wholesalePrice * quantity).toLocaleString()}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-600">Selling Price:</span>
                          <span>₹{(product.retailPrice * quantity).toLocaleString()}</span>
                        </div>
                        <div className="border-t pt-2 flex justify-between">
                          <span>Your Profit:</span>
                          <span className="text-green-600 text-xl">
                            ₹{calculateProfit().toLocaleString()}
                          </span>
                        </div>
                      </div>

                      <div className="flex gap-3">
                        <Button className="flex-1 bg-blue-600 hover:bg-blue-700">
                          Add to Cart
                        </Button>
                        <Button variant="outline" size="icon">
                          <Share2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                {/* Product Features */}
                <Card>
                  <CardContent className="pt-6">
                    <div className="grid grid-cols-2 gap-4">
                      <div className="flex items-center gap-3">
                        <div className="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                          <Truck className="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                          <div className="text-sm">Fast Shipping</div>
                          <div className="text-xs text-gray-500">3-5 days</div>
                        </div>
                      </div>
                      <div className="flex items-center gap-3">
                        <div className="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                          <Shield className="h-5 w-5 text-green-600" />
                        </div>
                        <div>
                          <div className="text-sm">Quality Assured</div>
                          <div className="text-xs text-gray-500">100% Genuine</div>
                        </div>
                      </div>
                      <div className="flex items-center gap-3">
                        <div className="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center">
                          <Clock className="h-5 w-5 text-purple-600" />
                        </div>
                        <div>
                          <div className="text-sm">In Stock</div>
                          <div className="text-xs text-gray-500">{product.stock} units</div>
                        </div>
                      </div>
                      <div className="flex items-center gap-3">
                        <div className="h-10 w-10 rounded-lg bg-orange-100 flex items-center justify-center">
                          <Download className="h-5 w-5 text-orange-600" />
                        </div>
                        <div>
                          <div className="text-sm">Media Pack</div>
                          <div className="text-xs text-gray-500">Available</div>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </>
            ) : (
              <Card>
                <CardContent className="pt-6">
                  <div className="text-center py-8">
                    <div className="bg-blue-50 text-blue-700 p-6 rounded-lg mb-4">
                      <div className="text-3xl mb-2">₹{product.wholesalePrice}</div>
                      <div className="text-sm">Wholesale Price (Login Required)</div>
                      <div className="text-xs mt-2">Potential Margin: {calculateMargin()}%</div>
                    </div>
                    <p className="text-gray-600 mb-4">
                      Register as a reseller to view pricing and start earning
                    </p>
                    <Button 
                      className="w-full bg-blue-600 hover:bg-blue-700"
                      onClick={() => onNavigate('become-reseller')}
                    >
                      Become a Reseller
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}
          </div>
        </div>

        {/* Product Details Tabs */}
        <Card>
          <CardContent className="pt-6">
            <Tabs defaultValue="description">
              <TabsList>
                <TabsTrigger value="description">Description</TabsTrigger>
                <TabsTrigger value="specifications">Specifications</TabsTrigger>
                <TabsTrigger value="features">Features</TabsTrigger>
              </TabsList>
              <TabsContent value="description" className="pt-6">
                <p className="text-gray-600 leading-relaxed">{product.description}</p>
              </TabsContent>
              <TabsContent value="specifications" className="pt-6">
                <div className="grid md:grid-cols-2 gap-4">
                  {Object.entries(product.specifications).map(([key, value]) => (
                    <div key={key} className="flex justify-between border-b pb-2">
                      <span className="text-gray-600">{key}</span>
                      <span>{value}</span>
                    </div>
                  ))}
                </div>
              </TabsContent>
              <TabsContent value="features" className="pt-6">
                <ul className="grid md:grid-cols-2 gap-3">
                  {product.features.map((feature, index) => (
                    <li key={index} className="flex items-center gap-2">
                      <div className="h-2 w-2 rounded-full bg-blue-600" />
                      {feature}
                    </li>
                  ))}
                </ul>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
