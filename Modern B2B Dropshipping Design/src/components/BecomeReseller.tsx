import { useState } from 'react';
import { CheckCircle, Upload, User, Building, Mail, Phone, MapPin, CreditCard } from 'lucide-react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { Textarea } from './ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Alert, AlertDescription } from './ui/alert';
import { Checkbox } from './ui/checkbox';

interface BecomeResellerProps {
  onNavigate: (page: string) => void;
}

export function BecomeReseller({ onNavigate }: BecomeResellerProps) {
  const [submitted, setSubmitted] = useState(false);
  const [formData, setFormData] = useState({
    fullName: '',
    businessName: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    pincode: '',
    gstin: '',
    bankName: '',
    accountNumber: '',
    ifsc: '',
    agreed: false,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitted(true);
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const benefits = [
    'Access to 1500+ wholesale products',
    '50-100% profit margins on every sale',
    'Zero inventory investment',
    'Direct shipping to customers',
    'Real-time order tracking',
    'Instant commission payouts',
    'Dedicated support team',
    'Marketing materials & catalogs',
  ];

  if (submitted) {
    return (
      <div className="min-h-screen bg-gray-50 py-16">
        <div className="container mx-auto px-4 max-w-2xl">
          <Card className="text-center">
            <CardContent className="pt-12 pb-12">
              <div className="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <CheckCircle className="h-8 w-8 text-green-600" />
              </div>
              <h2 className="text-2xl mb-4">Application Submitted Successfully!</h2>
              <p className="text-gray-600 mb-6">
                Thank you for your interest in becoming an Aakaari reseller. We'll review your application 
                and get back to you within 24 hours.
              </p>
              <div className="bg-blue-50 p-4 rounded-lg mb-6 text-left">
                <h3 className="mb-2">What's Next?</h3>
                <ol className="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                  <li>Our team will verify your KYC documents</li>
                  <li>You'll receive an approval email with login credentials</li>
                  <li>Access your dashboard and start ordering</li>
                  <li>Share product links and start earning!</li>
                </ol>
              </div>
              <div className="flex gap-3 justify-center">
                <Button onClick={() => onNavigate('home')} variant="outline">
                  Back to Home
                </Button>
                <Button onClick={() => onNavigate('contact')} className="bg-blue-600 hover:bg-blue-700">
                  Contact Support
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-gradient-to-br from-blue-600 to-blue-700 text-white">
        <div className="container mx-auto px-4 py-12">
          <h1 className="text-3xl md:text-4xl mb-4">Become a Reseller</h1>
          <p className="text-blue-100 max-w-2xl">
            Join thousands of successful resellers and start your dropshipping business today
          </p>
        </div>
      </div>

      <div className="container mx-auto px-4 py-12">
        <div className="grid lg:grid-cols-3 gap-8">
          {/* Benefits Sidebar */}
          <div className="lg:col-span-1">
            <Card className="sticky top-4">
              <CardHeader>
                <CardTitle>Why Join Aakaari?</CardTitle>
              </CardHeader>
              <CardContent>
                <ul className="space-y-3">
                  {benefits.map((benefit, index) => (
                    <li key={index} className="flex items-start gap-2 text-sm">
                      <CheckCircle className="h-4 w-4 text-green-600 flex-shrink-0 mt-0.5" />
                      <span>{benefit}</span>
                    </li>
                  ))}
                </ul>
              </CardContent>
            </Card>
          </div>

          {/* Application Form */}
          <div className="lg:col-span-2">
            <Card>
              <CardHeader>
                <CardTitle>Reseller Application Form</CardTitle>
              </CardHeader>
              <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                  {/* Personal Information */}
                  <div>
                    <h3 className="mb-4 flex items-center gap-2">
                      <User className="h-5 w-5" />
                      Personal Information
                    </h3>
                    <div className="grid md:grid-cols-2 gap-4">
                      <div>
                        <Label htmlFor="fullName">Full Name *</Label>
                        <Input
                          id="fullName"
                          name="fullName"
                          value={formData.fullName}
                          onChange={handleChange}
                          required
                          placeholder="John Doe"
                        />
                      </div>
                      <div>
                        <Label htmlFor="businessName">Business Name</Label>
                        <Input
                          id="businessName"
                          name="businessName"
                          value={formData.businessName}
                          onChange={handleChange}
                          placeholder="Optional"
                        />
                      </div>
                      <div>
                        <Label htmlFor="email">Email Address *</Label>
                        <Input
                          id="email"
                          name="email"
                          type="email"
                          value={formData.email}
                          onChange={handleChange}
                          required
                          placeholder="john@example.com"
                        />
                      </div>
                      <div>
                        <Label htmlFor="phone">Phone Number *</Label>
                        <Input
                          id="phone"
                          name="phone"
                          type="tel"
                          value={formData.phone}
                          onChange={handleChange}
                          required
                          placeholder="+91 98765 43210"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Address */}
                  <div>
                    <h3 className="mb-4 flex items-center gap-2">
                      <MapPin className="h-5 w-5" />
                      Address Details
                    </h3>
                    <div className="space-y-4">
                      <div>
                        <Label htmlFor="address">Street Address *</Label>
                        <Textarea
                          id="address"
                          name="address"
                          value={formData.address}
                          onChange={handleChange}
                          required
                          rows={2}
                          placeholder="123 Main Street"
                        />
                      </div>
                      <div className="grid md:grid-cols-3 gap-4">
                        <div>
                          <Label htmlFor="city">City *</Label>
                          <Input
                            id="city"
                            name="city"
                            value={formData.city}
                            onChange={handleChange}
                            required
                          />
                        </div>
                        <div>
                          <Label htmlFor="state">State *</Label>
                          <Input
                            id="state"
                            name="state"
                            value={formData.state}
                            onChange={handleChange}
                            required
                          />
                        </div>
                        <div>
                          <Label htmlFor="pincode">Pincode *</Label>
                          <Input
                            id="pincode"
                            name="pincode"
                            value={formData.pincode}
                            onChange={handleChange}
                            required
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Business Details */}
                  <div>
                    <h3 className="mb-4 flex items-center gap-2">
                      <Building className="h-5 w-5" />
                      Business Details
                    </h3>
                    <div className="space-y-4">
                      <div>
                        <Label htmlFor="gstin">GSTIN (Optional)</Label>
                        <Input
                          id="gstin"
                          name="gstin"
                          value={formData.gstin}
                          onChange={handleChange}
                          placeholder="22AAAAA0000A1Z5"
                        />
                        <p className="text-xs text-gray-500 mt-1">
                          Provide GSTIN if you have GST registration
                        </p>
                      </div>
                      <div>
                        <Label htmlFor="idProof">ID Proof Upload *</Label>
                        <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer">
                          <Upload className="h-8 w-8 mx-auto mb-2 text-gray-400" />
                          <p className="text-sm text-gray-600 mb-1">
                            Click to upload or drag and drop
                          </p>
                          <p className="text-xs text-gray-500">
                            Aadhaar / PAN / Driving License (PDF, JPG, PNG)
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Bank Details */}
                  <div>
                    <h3 className="mb-4 flex items-center gap-2">
                      <CreditCard className="h-5 w-5" />
                      Bank Details
                    </h3>
                    <div className="grid md:grid-cols-2 gap-4">
                      <div className="md:col-span-2">
                        <Label htmlFor="bankName">Bank Name *</Label>
                        <Input
                          id="bankName"
                          name="bankName"
                          value={formData.bankName}
                          onChange={handleChange}
                          required
                          placeholder="State Bank of India"
                        />
                      </div>
                      <div>
                        <Label htmlFor="accountNumber">Account Number *</Label>
                        <Input
                          id="accountNumber"
                          name="accountNumber"
                          value={formData.accountNumber}
                          onChange={handleChange}
                          required
                          placeholder="1234567890"
                        />
                      </div>
                      <div>
                        <Label htmlFor="ifsc">IFSC Code *</Label>
                        <Input
                          id="ifsc"
                          name="ifsc"
                          value={formData.ifsc}
                          onChange={handleChange}
                          required
                          placeholder="SBIN0001234"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Terms & Conditions */}
                  <Alert>
                    <AlertDescription>
                      <div className="flex items-start gap-2">
                        <Checkbox
                          id="agreed"
                          checked={formData.agreed}
                          onCheckedChange={(checked) =>
                            setFormData({ ...formData, agreed: checked as boolean })
                          }
                        />
                        <label htmlFor="agreed" className="text-sm cursor-pointer">
                          I agree to the{' '}
                          <a href="#" className="text-blue-600 hover:underline">
                            Terms & Conditions
                          </a>
                          {' '}and{' '}
                          <a href="#" className="text-blue-600 hover:underline">
                            Reseller Agreement
                          </a>
                          . I understand that Aakaari will verify my KYC documents before approval.
                        </label>
                      </div>
                    </AlertDescription>
                  </Alert>

                  {/* Submit */}
                  <div className="flex gap-4">
                    <Button
                      type="button"
                      variant="outline"
                      onClick={() => onNavigate('home')}
                      className="flex-1"
                    >
                      Cancel
                    </Button>
                    <Button
                      type="submit"
                      disabled={!formData.agreed}
                      className="flex-1 bg-blue-600 hover:bg-blue-700"
                    >
                      Submit Application
                    </Button>
                  </div>
                </form>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
