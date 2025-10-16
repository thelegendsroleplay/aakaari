import { useState } from 'react';
import { Search, Filter, ChevronDown, TrendingUp, Download } from 'lucide-react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Card, CardContent } from './ui/card';
import { Badge } from './ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from './ui/sheet';
import { Checkbox } from './ui/checkbox';
import { ImageWithFallback } from './figma/ImageWithFallback';

interface Product {
  id: string;
  name: string;
  sku: string;
  category: string;
  wholesalePrice: number;
  retailPrice: number;
  moq: number;
  margin: number;
  stock: number;
  image: string;
  tag?: string;
}

interface ProductCatalogProps {
  onNavigate: (page: string, productId?: string) => void;
  isReseller?: boolean;
}

export function ProductCatalog({ onNavigate, isReseller = false }: ProductCatalogProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategories, setSelectedCategories] = useState<string[]>([]);
  const [sortBy, setSortBy] = useState('featured');

  const categories = [
    'Fashion & Apparel',
    'Electronics',
    'Home & Living',
    'Beauty & Care',
    'Accessories',
    'Sports & Fitness',
  ];

  const products: Product[] = [
    {
      id: '1',
      name: 'Premium Cotton T-Shirt',
      sku: 'AAK-TSH-001',
      category: 'Fashion & Apparel',
      wholesalePrice: 120,
      retailPrice: 299,
      moq: 10,
      margin: 149,
      stock: 500,
      image: 'https://images.unsplash.com/photo-1632337950445-ba446cb0e26f?w=400',
      tag: 'Best Seller',
    },
    {
      id: '2',
      name: 'Wireless Earbuds Pro',
      sku: 'AAK-EAR-002',
      category: 'Electronics',
      wholesalePrice: 450,
      retailPrice: 999,
      moq: 5,
      margin: 122,
      stock: 200,
      image: 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=400',
      tag: 'New',
    },
    {
      id: '3',
      name: 'Ceramic Coffee Mug Set',
      sku: 'AAK-MUG-003',
      category: 'Home & Living',
      wholesalePrice: 180,
      retailPrice: 399,
      moq: 12,
      margin: 122,
      stock: 350,
      image: 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=400',
    },
    {
      id: '4',
      name: 'Face Serum Collection',
      sku: 'AAK-SER-004',
      category: 'Beauty & Care',
      wholesalePrice: 280,
      retailPrice: 599,
      moq: 6,
      margin: 114,
      stock: 150,
      image: 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=400',
      tag: 'Best Seller',
    },
    {
      id: '5',
      name: 'Leather Wallet',
      sku: 'AAK-WAL-005',
      category: 'Accessories',
      wholesalePrice: 200,
      retailPrice: 499,
      moq: 8,
      margin: 150,
      stock: 400,
      image: 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=400',
    },
    {
      id: '6',
      name: 'Yoga Mat Premium',
      sku: 'AAK-YOG-006',
      category: 'Sports & Fitness',
      wholesalePrice: 350,
      retailPrice: 799,
      moq: 5,
      margin: 128,
      stock: 180,
      image: 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=400',
      tag: 'New',
    },
    {
      id: '7',
      name: 'Denim Jeans',
      sku: 'AAK-JEN-007',
      category: 'Fashion & Apparel',
      wholesalePrice: 380,
      retailPrice: 899,
      moq: 6,
      margin: 137,
      stock: 300,
      image: 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=400',
    },
    {
      id: '8',
      name: 'Smart Watch Lite',
      sku: 'AAK-WAT-008',
      category: 'Electronics',
      wholesalePrice: 800,
      retailPrice: 1999,
      moq: 3,
      margin: 150,
      stock: 120,
      image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400',
      tag: 'Best Seller',
    },
  ];

  const filteredProducts = products.filter((product) => {
    const matchesSearch = product.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                          product.sku.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = selectedCategories.length === 0 || 
                            selectedCategories.includes(product.category);
    return matchesSearch && matchesCategory;
  });

  const toggleCategory = (category: string) => {
    setSelectedCategories((prev) =>
      prev.includes(category)
        ? prev.filter((c) => c !== category)
        : [...prev, category]
    );
  };

  const FilterPanel = () => (
    <div className="space-y-6">
      <div>
        <h3 className="mb-4">Categories</h3>
        <div className="space-y-3">
          {categories.map((category) => (
            <div key={category} className="flex items-center gap-2">
              <Checkbox
                id={category}
                checked={selectedCategories.includes(category)}
                onCheckedChange={() => toggleCategory(category)}
              />
              <label htmlFor={category} className="text-sm cursor-pointer">
                {category}
              </label>
            </div>
          ))}
        </div>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="container mx-auto px-4 py-8">
          <h1 className="text-3xl md:text-4xl mb-2">Product Catalog</h1>
          <p className="text-gray-600">
            {isReseller 
              ? 'Browse wholesale products and start selling'
              : 'Register as a reseller to view wholesale prices'
            }
          </p>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Desktop Filters */}
          <aside className="hidden lg:block w-64 flex-shrink-0">
            <Card>
              <CardContent className="pt-6">
                <FilterPanel />
              </CardContent>
            </Card>
          </aside>

          {/* Main Content */}
          <div className="flex-1">
            {/* Search & Sort */}
            <div className="flex flex-col sm:flex-row gap-4 mb-6">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search products or SKU..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
              <div className="flex gap-2">
                <Sheet>
                  <SheetTrigger asChild className="lg:hidden">
                    <Button variant="outline">
                      <Filter className="h-4 w-4 mr-2" />
                      Filters
                    </Button>
                  </SheetTrigger>
                  <SheetContent side="left">
                    <SheetHeader>
                      <SheetTitle>Filters</SheetTitle>
                    </SheetHeader>
                    <div className="mt-6">
                      <FilterPanel />
                    </div>
                  </SheetContent>
                </Sheet>
                <Select value={sortBy} onValueChange={setSortBy}>
                  <SelectTrigger className="w-[180px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="featured">Featured</SelectItem>
                    <SelectItem value="price-low">Price: Low to High</SelectItem>
                    <SelectItem value="price-high">Price: High to Low</SelectItem>
                    <SelectItem value="margin">Highest Margin</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* Results Count */}
            <div className="mb-4 text-sm text-gray-600">
              Showing {filteredProducts.length} products
            </div>

            {/* Product Grid */}
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {filteredProducts.map((product) => (
                <Card 
                  key={product.id}
                  className="group cursor-pointer hover:shadow-lg transition-shadow"
                  onClick={() => onNavigate('product-detail', product.id)}
                >
                  <CardContent className="p-0">
                    <div className="relative overflow-hidden">
                      <ImageWithFallback
                        src={product.image}
                        alt={product.name}
                        className="w-full h-56 object-cover group-hover:scale-105 transition-transform"
                      />
                      {product.tag && (
                        <Badge className="absolute top-2 left-2 bg-blue-600">
                          {product.tag}
                        </Badge>
                      )}
                      <Badge className="absolute top-2 right-2 bg-green-500">
                        {product.margin}% Margin
                      </Badge>
                    </div>
                    <div className="p-4">
                      <div className="text-xs text-gray-500 mb-1">{product.sku}</div>
                      <h3 className="mb-3 group-hover:text-blue-600 transition-colors">
                        {product.name}
                      </h3>
                      
                      {isReseller ? (
                        <>
                          <div className="flex justify-between items-center mb-3">
                            <div>
                              <div className="text-xs text-gray-500">Wholesale</div>
                              <div className="text-blue-600">
                                ₹{product.wholesalePrice}
                              </div>
                            </div>
                            <div>
                              <div className="text-xs text-gray-500">Suggested Retail</div>
                              <div className="text-green-600">
                                ₹{product.retailPrice}
                              </div>
                            </div>
                          </div>
                          <div className="flex justify-between items-center text-xs text-gray-500 mb-3">
                            <span>MOQ: {product.moq} pcs</span>
                            <span>Stock: {product.stock}</span>
                          </div>
                          <Button className="w-full bg-blue-600 hover:bg-blue-700">
                            View Details
                          </Button>
                        </>
                      ) : (
                        <>
                          <div className="bg-blue-50 text-blue-700 text-sm p-3 rounded-md mb-3">
                            <span className="block mb-1">Login to view wholesale prices</span>
                            <span className="text-xs">Potential margin: {product.margin}%</span>
                          </div>
                          <Button 
                            className="w-full"
                            onClick={(e) => {
                              e.stopPropagation();
                              onNavigate('become-reseller');
                            }}
                          >
                            Become a Reseller
                          </Button>
                        </>
                      )}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>

            {filteredProducts.length === 0 && (
              <div className="text-center py-12">
                <p className="text-gray-500">No products found matching your criteria.</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
