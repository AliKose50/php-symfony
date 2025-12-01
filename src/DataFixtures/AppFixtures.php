<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Kategoriler zaten var mı kontrol et
        $categoryRepository = $manager->getRepository(Category::class);
        $womenCategory = $categoryRepository->findOneBy(['name' => 'Kadın']);
        if (!$womenCategory) {
            $womenCategory = new Category();
            $womenCategory->setName('Kadın');
            $manager->persist($womenCategory);
        }

        $menCategory = $categoryRepository->findOneBy(['name' => 'Erkek']);
        if (!$menCategory) {
            $menCategory = new Category();
            $menCategory->setName('Erkek');
            $manager->persist($menCategory);
        }

        $manager->flush();

        // Ürünler zaten var mı kontrol et
        $productRepository = $manager->getRepository(Product::class);
        $existingProducts = $productRepository->findAll();
        if (count($existingProducts) > 0) {
            // Ürünler zaten varsa yükleme
            return;
        }

        // Kadın Ürünleri
        $products = [
            [
                'name' => 'Casual Gri Hoodie',
                'description' => 'Rahat ve stiliniz günlük aktiviteler için mükemmel.',
                'price' => '299.99',
                'stock' => 50,
                'category' => $womenCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1556821552-9f6db051d754?w=400&h=400&fit=crop',
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Mavi Denim Ceket',
                'description' => 'Klasik denim ceket, her mevsim giyilebilir.',
                'price' => '449.99',
                'stock' => 35,
                'category' => $womenCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1551028719-00167b16ebc5?w=400&h=400&fit=crop',
                    'https://images.unsplash.com/photo-1528541521131-d4b1c0ee6014?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Beyaz Tişört',
                'description' => 'Temiz ve minimalist tasarım, tüm kıyafetlerle uyumlu.',
                'price' => '129.99',
                'stock' => 100,
                'category' => $womenCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Siyah Pantolon',
                'description' => 'Rahat ve formal giyim için uygun siyah pantolon.',
                'price' => '249.99',
                'stock' => 40,
                'category' => $womenCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1542272604-787c62d465d1?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Kırmızı Elbise',
                'description' => 'Özel günler için şık ve dikkat çekici elbise.',
                'price' => '399.99',
                'stock' => 20,
                'category' => $womenCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1612336307429-8a88e8d08ee3?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Yeşil Ceket',
                'description' => 'Trend renkle dikkat çeken ceket.',
                'price' => '379.99',
                'stock' => 25,
                'category' => $womenCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1551028719-00167b16ebc5?w=400&h=400&fit=crop',
                ]
            ],
        ];

        // Erkek Ürünleri
        $menProducts = [
            [
                'name' => 'Siyah Spor Hoodie',
                'description' => 'Sporty tasarım ve konfor birleşimi.',
                'price' => '349.99',
                'stock' => 45,
                'category' => $menCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1556821552-9f6db051d754?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Kahverengi Ceket',
                'description' => 'Klasik renk ve tasarım ile uzun ömürlü.',
                'price' => '499.99',
                'stock' => 30,
                'category' => $menCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1528541521131-d4b1c0ee6014?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Beyaz Spor Tişört',
                'description' => 'Hava geçirmeli ve konforlu spor tişörtü.',
                'price' => '149.99',
                'stock' => 80,
                'category' => $menCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Koyu Mavi Jean',
                'description' => 'Dayanıklı ve rahat günlük jean.',
                'price' => '289.99',
                'stock' => 60,
                'category' => $menCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1542272604-787c62d465d1?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Açık Gri Pantolon',
                'description' => 'Formal ve casual kullanıma uygun pantolon.',
                'price' => '269.99',
                'stock' => 35,
                'category' => $menCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=400&h=400&fit=crop',
                ]
            ],
            [
                'name' => 'Mavi Spor Ceketi',
                'description' => 'Outdoor aktiviteler için hafif ve fonksiyonel.',
                'price' => '459.99',
                'stock' => 22,
                'category' => $menCategory,
                'images' => [
                    'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400&h=400&fit=crop',
                ]
            ],
        ];

        // Ürünleri ve resimlerini ekle
        foreach (array_merge($products, $menProducts) as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setStock($productData['stock']);
            $product->setCategory($productData['category']);
            $manager->persist($product);

            // Resimleri ekle
            foreach ($productData['images'] as $index => $imageUrl) {
                $productImage = new ProductImage();
                $productImage->setImageUrl($imageUrl);
                $productImage->setIsMain($index === 0); // İlk resim ana resim
                $productImage->setProduct($product);
                $manager->persist($productImage);
            }
        }

        $manager->flush();
    }
}
