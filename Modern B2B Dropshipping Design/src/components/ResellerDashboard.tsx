import { useState } from 'react';
import { 
  LayoutDashboard, 
  ShoppingCart, 
  Wallet, 
  Download, 
  Package, 
  TrendingUp,
  DollarSign,
  CreditCard,
  ExternalLink,
  Upload,
  Settings
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import { Badge } from './ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from './ui/table';
import { Progress } from './ui/progress';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { Alert, AlertDescription } from './ui/alert';

interface ResellerDashboardProps {
  onNavigate: (page: string) => void;
}

export function ResellerDashboard({ onNavigate }: ResellerDashboardProps) {
  const [activeTab, setActiveTab] = useState('overview');

  const stats = [
    {
      title: 'Total Orders',
      value: '127',
      change: '+12%',
      icon: ShoppingCart,
      color: 'text-blue-600',
      bg: 'bg-blue-100',
    },
    {
      title: 'Total Earnings',
      value: '₹45,280',
      change: '+23%',
      icon: TrendingUp,
      color: 'text-green-600',
      bg: 'bg-green-100',
    },
    {
      title: 'Wallet Balance',
      value: '₹12,450',
      change: 'Available',
      icon: Wallet,
      color: 'text-purple-600',
      bg: 'bg-purple-100',
    },
    {
      title: 'Active Products',
      value: '45',
      change: 'Selling',
      icon: Package,
      color: 'text-orange-600',
      bg: 'bg-orange-100',
    },
  ];

  const recentOrders = [
    {
      id: '#ORD-1234',
      date: '2025-10-15',
      customer: 'Rahul Sharma',
      products: 3,
      amount: 1850,
      commission: 925,
      status: 'Delivered',
    },
    {
      id: '#ORD-1235',
      date: '2025-10-14',
      customer: 'Priya Patel',
      products: 2,
      amount: 1200,
      commission: 600,
      status: 'Shipped',
    },
    {
      id: '#ORD-1236',
      date: '2025-10-13',
      customer: 'Amit Kumar',
      products: 5,
      amount: 3450,
      commission: 1725,
      status: 'Processing',
    },
  ];

  const walletTransactions = [
    {
      id: 'TXN-001',
      type: 'Credit',
      description: 'Commission from #ORD-1234',
      amount: 925,
      date: '2025-10-15',
    },
    {
      id: 'TXN-002',
      type: 'Debit',
      description: 'Withdrawal to bank',
      amount: -5000,
      date: '2025-10-14',
    },
    {
      id: 'TXN-003',
      type: 'Credit',
      description: 'Commission from #ORD-1233',
      amount: 1200,
      date: '2025-10-13',
    },
  ];

  const downloadableAssets = [
    {
      name: 'Product Catalog - October 2025',
      type: 'PDF',
      size: '12.5 MB',
      date: '2025-10-01',
    },
    {
      name: 'Product Images - Fashion',
      type: 'ZIP',
      size: '156 MB',
      date: '2025-10-01',
    },
    {
      name: 'Marketing Templates',
      type: 'ZIP',
      size: '45 MB',
      date: '2025-09-28',
    },
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="container mx-auto px-4 py-6">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 className="text-2xl md:text-3xl mb-1">Reseller Dashboard</h1>
              <p className="text-gray-600">Welcome back, Rajesh Kumar!</p>
            </div>
            <div className="flex gap-3">
              <Button onClick={() => onNavigate('products')} className="bg-blue-600 hover:bg-blue-700">
                <ShoppingCart className="h-4 w-4 mr-2" />
                Browse Products
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-8">
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="mb-6">
            <TabsTrigger value="overview">
              <LayoutDashboard className="h-4 w-4 mr-2" />
              Overview
            </TabsTrigger>
            <TabsTrigger value="orders">
              <ShoppingCart className="h-4 w-4 mr-2" />
              Orders
            </TabsTrigger>
            <TabsTrigger value="bulk-order">
              <Upload className="h-4 w-4 mr-2" />
              Bulk Order
            </TabsTrigger>
            <TabsTrigger value="wallet">
              <Wallet className="h-4 w-4 mr-2" />
              Wallet
            </TabsTrigger>
            <TabsTrigger value="downloads">
              <Download className="h-4 w-4 mr-2" />
              Downloads
            </TabsTrigger>
          </TabsList>

          {/* Overview Tab */}
          <TabsContent value="overview" className="space-y-6">
            {/* Stats Grid */}
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {stats.map((stat, index) => (
                <Card key={index}>
                  <CardContent className="pt-6">
                    <div className="flex items-center justify-between mb-4">
                      <div className={`h-12 w-12 rounded-lg ${stat.bg} flex items-center justify-center`}>
                        <stat.icon className={`h-6 w-6 ${stat.color}`} />
                      </div>
                      <Badge variant="secondary" className="text-xs">
                        {stat.change}
                      </Badge>
                    </div>
                    <div className="text-2xl mb-1">{stat.value}</div>
                    <div className="text-sm text-gray-600">{stat.title}</div>
                  </CardContent>
                </Card>
              ))}
            </div>

            {/* Recent Orders */}
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle>Recent Orders</CardTitle>
                  <Button variant="ghost" size="sm">View All</Button>
                </div>
              </CardHeader>
              <CardContent>
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Order ID</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Commission</TableHead>
                        <TableHead>Status</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {recentOrders.map((order) => (
                        <TableRow key={order.id}>
                          <TableCell className="font-medium">{order.id}</TableCell>
                          <TableCell>{order.date}</TableCell>
                          <TableCell>{order.customer}</TableCell>
                          <TableCell>₹{order.amount}</TableCell>
                          <TableCell className="text-green-600">₹{order.commission}</TableCell>
                          <TableCell>
                            <Badge
                              variant={
                                order.status === 'Delivered'
                                  ? 'default'
                                  : order.status === 'Shipped'
                                  ? 'secondary'
                                  : 'outline'
                              }
                            >
                              {order.status}
                            </Badge>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              </CardContent>
            </Card>

            {/* Quick Actions */}
            <div className="grid md:grid-cols-3 gap-6">
              <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                <CardContent className="pt-6">
                  <div className="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center mb-4">
                    <ExternalLink className="h-6 w-6 text-blue-600" />
                  </div>
                  <h3 className="mb-2">My Store Links</h3>
                  <p className="text-sm text-gray-600 mb-4">
                    Generate and share your personalized product links
                  </p>
                  <Button variant="outline" className="w-full">Generate Link</Button>
                </CardContent>
              </Card>

              <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                <CardContent className="pt-6">
                  <div className="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center mb-4">
                    <CreditCard className="h-6 w-6 text-green-600" />
                  </div>
                  <h3 className="mb-2">Request Withdrawal</h3>
                  <p className="text-sm text-gray-600 mb-4">
                    Withdraw your earnings to your bank account
                  </p>
                  <Button variant="outline" className="w-full">Withdraw Funds</Button>
                </CardContent>
              </Card>

              <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                <CardContent className="pt-6">
                  <div className="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center mb-4">
                    <Download className="h-6 w-6 text-purple-600" />
                  </div>
                  <h3 className="mb-2">Download Catalog</h3>
                  <p className="text-sm text-gray-600 mb-4">
                    Get the latest product catalog and images
                  </p>
                  <Button variant="outline" className="w-full">Download Now</Button>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Orders Tab */}
          <TabsContent value="orders" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>All Orders</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Order ID</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead>Customer</TableHead>
                        <TableHead>Products</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Commission</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Action</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {recentOrders.map((order) => (
                        <TableRow key={order.id}>
                          <TableCell className="font-medium">{order.id}</TableCell>
                          <TableCell>{order.date}</TableCell>
                          <TableCell>{order.customer}</TableCell>
                          <TableCell>{order.products} items</TableCell>
                          <TableCell>₹{order.amount}</TableCell>
                          <TableCell className="text-green-600">₹{order.commission}</TableCell>
                          <TableCell>
                            <Badge
                              variant={
                                order.status === 'Delivered'
                                  ? 'default'
                                  : order.status === 'Shipped'
                                  ? 'secondary'
                                  : 'outline'
                              }
                            >
                              {order.status}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <Button variant="ghost" size="sm">View</Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Bulk Order Tab */}
          <TabsContent value="bulk-order" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Place Bulk Order</CardTitle>
              </CardHeader>
              <CardContent className="space-y-6">
                <Alert>
                  <AlertDescription>
                    Upload a CSV file with product SKUs and quantities to place bulk orders quickly. 
                    Download our template to get started.
                  </AlertDescription>
                </Alert>

                <div className="space-y-4">
                  <div>
                    <Button variant="outline" size="sm">
                      <Download className="h-4 w-4 mr-2" />
                      Download CSV Template
                    </Button>
                  </div>

                  <div className="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-blue-500 transition-colors cursor-pointer">
                    <Upload className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                    <h3 className="mb-2">Upload CSV File</h3>
                    <p className="text-sm text-gray-600 mb-4">
                      Click to upload or drag and drop your CSV file here
                    </p>
                    <Button>Choose File</Button>
                  </div>

                  <div className="bg-blue-50 p-4 rounded-lg">
                    <h4 className="mb-2">CSV Format:</h4>
                    <pre className="text-xs bg-white p-3 rounded overflow-x-auto">
                      SKU, Quantity{'\n'}
                      AAK-TSH-001, 50{'\n'}
                      AAK-EAR-002, 20{'\n'}
                      AAK-MUG-003, 100
                    </pre>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Wallet Tab */}
          <TabsContent value="wallet" className="space-y-6">
            <div className="grid md:grid-cols-2 gap-6">
              <Card>
                <CardHeader>
                  <CardTitle>Wallet Balance</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-4xl mb-2">₹12,450</div>
                  <p className="text-sm text-gray-600 mb-6">Available for withdrawal</p>
                  <Button className="w-full bg-blue-600 hover:bg-blue-700">
                    <CreditCard className="h-4 w-4 mr-2" />
                    Withdraw to Bank
                  </Button>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Earnings This Month</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-4xl mb-2">₹8,720</div>
                  <p className="text-sm text-gray-600 mb-4">From 24 orders</p>
                  <Progress value={65} className="mb-2" />
                  <p className="text-xs text-gray-500">65% of monthly goal (₹15,000)</p>
                </CardContent>
              </Card>
            </div>

            <Card>
              <CardHeader>
                <CardTitle>Recent Transactions</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Transaction ID</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead className="text-right">Amount</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {walletTransactions.map((txn) => (
                        <TableRow key={txn.id}>
                          <TableCell className="font-medium">{txn.id}</TableCell>
                          <TableCell>
                            <Badge variant={txn.type === 'Credit' ? 'default' : 'secondary'}>
                              {txn.type}
                            </Badge>
                          </TableCell>
                          <TableCell>{txn.description}</TableCell>
                          <TableCell>{txn.date}</TableCell>
                          <TableCell className={`text-right ${txn.amount > 0 ? 'text-green-600' : 'text-red-600'}`}>
                            {txn.amount > 0 ? '+' : ''}₹{Math.abs(txn.amount)}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* Downloads Tab */}
          <TabsContent value="downloads" className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Download Center</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {downloadableAssets.map((asset, index) => (
                    <div
                      key={index}
                      className="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50"
                    >
                      <div className="flex items-center gap-4">
                        <div className="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                          <Download className="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                          <h4>{asset.name}</h4>
                          <p className="text-sm text-gray-600">
                            {asset.type} • {asset.size} • {asset.date}
                          </p>
                        </div>
                      </div>
                      <Button variant="outline">
                        <Download className="h-4 w-4 mr-2" />
                        Download
                      </Button>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}
