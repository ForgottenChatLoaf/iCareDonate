/**
 * iCareDonate — Mock Data Store
 * frontend/js/data.js
 */

const ICD_DATA = {
  categories: [
    {
      id: 1,
      slug: 'emergency',
      name: 'Emergency & Disaster Relief',
      icon: 'flame',
      gradient: 'linear-gradient(135deg, #BC4E2B 0%, #E0553D 50%, #F2904A 100%)',
      desc: 'Essential immediate sustenance, clean water, and shelter supplies for urgent relief.',
      accepted: 'Food packs, canned goods, blankets, flashlights',
      image: 'assets/images/categories/emergency-disaster-relief.jpg'
    },
    {
      id: 2,
      slug: 'school-supplies',
      name: 'School Supplies',
      icon: 'book-open',
      gradient: 'linear-gradient(135deg, #0E3D8A 0%, #1F4FA3 50%, #4278D1 100%)',
      desc: 'Learning materials, notebooks, bags, and supplies empowering young students in local schools.',
      accepted: 'School bags, notebooks, pens, crayons, calculators',
      image: 'assets/images/categories/school-supplies.jpg'
    },
    {
      id: 3,
      slug: 'food-groceries',
      name: 'Food & Groceries',
      icon: 'utensils',
      gradient: 'linear-gradient(135deg, #1C6339 0%, #2F9452 50%, #58C67F 100%)',
      desc: 'Canned goods, dry rations, packaged staples, baby food, and sealed meal kits.',
      accepted: 'Canned goods, rice sacks, powdered milk, instant oatmeal',
      image: 'assets/images/categories/food-groceries.jpg'
    },
    {
      id: 4,
      slug: 'medical-hygiene',
      name: 'Healthcare & Hygiene',
      icon: 'heart-pulse',
      gradient: 'linear-gradient(135deg, #A0244E 0%, #D4456B 50%, #E87A8C 100%)',
      desc: 'Hygiene and basic wellness kits supporting vulnerable community members and seniors.',
      accepted: 'Soap, toothbrushes, sanitary napkins, first-aid, vitamins',
      image: 'assets/images/categories/medical-first-aid.jpg'
    },
    {
      id: 5,
      slug: 'clothing-shoes',
      name: 'Clothing & Shoes',
      icon: 'shirt',
      gradient: 'linear-gradient(135deg, #3D2B8A 0%, #6345D4 50%, #8E7AE8 100%)',
      desc: 'Clean wearables, uniforms, shoes, jackets, and everyday garments for all ages.',
      accepted: 'School uniforms, rubber shoes, jackets, baby clothes',
      image: 'assets/images/categories/clothing-shoes.avif'
    },
    {
      id: 6,
      slug: 'electronics-gadgets',
      name: 'Electronics & Gadgets',
      icon: 'laptop',
      gradient: 'linear-gradient(135deg, #094769 0%, #1274A5 50%, #3AB4E8 100%)',
      desc: 'Student laptops, tablets, chargers, mobile devices, and educational tech accessories.',
      accepted: 'Working laptops, Android tablets, power banks, chargers',
      image: 'assets/images/categories/electronics-gadgets.avif'
    },
    {
      id: 7,
      slug: 'household-goods',
      name: 'Household Goods',
      icon: 'home',
      gradient: 'linear-gradient(135deg, #7A4B1A 0%, #B8732A 50%, #E4A252 100%)',
      desc: 'Kitchenware, bedding, small appliances, organizers, and home necessities.',
      accepted: 'Electric fans, rice cookers, thermal blankets, kitchen utensils',
      image: 'assets/images/categories/other-essentials.jpg'
    },
    {
      id: 8,
      slug: 'toys-games',
      name: 'Toys & Games',
      icon: 'smile',
      gradient: 'linear-gradient(135deg, #8A1A6C 0%, #BA2892 50%, #E85CC3 100%)',
      desc: 'Educational toys, puzzles, books, and recreational materials for community children.',
      accepted: 'Educational puzzles, building blocks, children storybooks',
      image: 'assets/images/categories/toys-games.avif'
    }
  ],

  donations: [
    {
      id: 1,
      title: 'Elementary School Backpacks & Notebooks Set',
      categoryId: 2,
      categorySlug: 'school-supplies',
      categoryName: 'School Supplies',
      image: 'assets/images/categories/school-supplies.jpg',
      quantity: 25,
      location: 'Pasig City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Elena Ramos',
      donorRole: 'Verified Community Donor',
      verifiedDate: 'October 10, 2026',
      description: 'Brand new sturdy elementary school backpacks equipped with 5 spiral notebooks, a pack of pencils, crayons, and an eraser each. Ideal for incoming grade 1–4 public school learners.',
      instructions: 'Pick-up is arranged at Barangay San Antonio Hall between 9:00 AM and 4:00 PM on weekdays after request approval.'
    },
    {
      id: 2,
      title: 'Assorted Canned Goods and Rice Sacks',
      categoryId: 3,
      categorySlug: 'food-groceries',
      categoryName: 'Food & Groceries',
      image: 'assets/images/categories/food-groceries.jpg',
      quantity: 35,
      location: 'Quezon City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Community Kitchen Group',
      donorRole: 'Verified Local Organization',
      verifiedDate: 'October 8, 2026',
      description: 'Care packages each containing 5kg well-milled rice, 6 cans of sardines, 4 cans of corned beef, and 3 packs of instant noodles. Packaged in sealed eco-bags.',
      instructions: 'Please bring a valid community ID. Pickup coordination via admin dispatch confirmation.'
    },
    {
      id: 3,
      title: 'Working Lenovo Student Laptops with Chargers',
      categoryId: 6,
      categorySlug: 'electronics-gadgets',
      categoryName: 'Electronics & Gadgets',
      image: 'assets/images/categories/school-supplies-alt.avif',
      quantity: 5,
      location: 'Makati City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'TechForGood PH',
      donorRole: 'Corporate CSR Partner',
      verifiedDate: 'October 6, 2026',
      description: 'Refurbished Lenovo ThinkPads with clean Windows 10 installations, webcam, WiFi, and functional chargers. Intended for senior high school or college students in online classes.',
      instructions: 'Proof of student enrollment required upon pickup verification.'
    },
    {
      id: 4,
      title: 'Emergency Family First-Aid & Hygiene Kits',
      categoryId: 4,
      categorySlug: 'medical-hygiene',
      categoryName: 'Healthcare & Hygiene',
      image: 'assets/images/categories/medical-first-aid.jpg',
      quantity: 40,
      location: 'Taguig City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Red Crescent Volunteer Circle',
      donorRole: 'Medical NGO',
      verifiedDate: 'October 5, 2026',
      description: 'Includes alcohol spray, sterile gauze, povidone-iodine, paracetamol strips, oral rehydration salts, bath soap, and toothbrush sets.',
      instructions: 'Available for immediate pickup by registered community healthcare coordinators or affected families.'
    },
    {
      id: 5,
      title: 'Children’s Rain Jackets & Durable Rubber Shoes',
      categoryId: 5,
      categorySlug: 'clothing-shoes',
      categoryName: 'Clothing & Shoes',
      image: 'assets/images/categories/clothing-shoes.avif',
      quantity: 30,
      location: 'Marikina City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Maria Santos',
      donorRole: 'Verified Community Donor',
      verifiedDate: 'October 4, 2026',
      description: 'Clean, gently used and new rain gear for kids aged 5 to 12. Includes water-resistant hoodies, boots, and non-slip athletic shoes.',
      instructions: 'Pick-up at Marikina Sports Center administrative gate.'
    },
    {
      id: 6,
      title: 'Heavy-Duty Thermal Blankets & Disaster Cots',
      categoryId: 1,
      categorySlug: 'emergency',
      categoryName: 'Emergency & Disaster Relief',
      image: 'assets/images/categories/emergency-disaster-relief.jpg',
      quantity: 18,
      location: 'Rizal Province',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Disaster Preparedness Fund',
      donorRole: 'Community Relief Group',
      verifiedDate: 'October 3, 2026',
      description: 'Thick thermal wool blankets and collapsible ground mats suitable for families affected by recent seasonal flooding or localized fires.',
      instructions: 'Dispatched through local disaster risk reduction officers.'
    },
    {
      id: 7,
      title: 'Wooden Building Blocks & Early Learning Puzzles',
      categoryId: 8,
      categorySlug: 'toys-games',
      categoryName: 'Toys & Games',
      image: 'assets/images/categories/toys-games.avif',
      quantity: 15,
      location: 'Mandaluyong City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Joyful Play Foundation',
      donorRole: 'Verified NGO',
      verifiedDate: 'October 2, 2026',
      description: 'Non-toxic wooden blocks, shape sorters, alphabet cards, and children storybooks suitable for day care centers and low-income nursery learners.',
      instructions: 'Priority given to day-care teachers and registered mothers.'
    },
    {
      id: 8,
      title: 'Stainless Steel Cookware Sets & Electric Kettles',
      categoryId: 7,
      categorySlug: 'household-goods',
      categoryName: 'Household Goods',
      image: 'assets/images/categories/other-essentials.jpg',
      quantity: 12,
      location: 'San Juan City, Metro Manila',
      pickupType: 'Direct Local Pick-up',
      status: 'Approved',
      donorName: 'Bayanihan Home Helpers',
      donorRole: 'Community Group',
      verifiedDate: 'October 1, 2026',
      description: 'Durable frying pans, cooking pots, ladles, and energy-efficient 1.8L electric kettles for families establishing new temporary living quarters.',
      instructions: 'Standard pickup with Barangay endorsement.'
    }
  ],

  faqs: [
    {
      q: 'Is iCareDonate 100% free to use?',
      a: 'Yes! iCareDonate is a strictly non-monetary public service platform. No fees are ever charged for donating, requesting, or picking up items.'
    },
    {
      q: 'How does item verification work?',
      a: 'Every donation listing and aid request submitted is reviewed by an administrator before appearing publicly. We verify item condition, donor details, and proof of need to prevent fraud.'
    },
    {
      q: 'How do pick-ups and handovers take place?',
      a: 'All exchanges occur via local, direct pick-ups at designated public locations (such as barangay halls, community centers, or verified meeting points) ensuring complete safety and zero shipping costs.'
    },
    {
      q: 'What proof is required when requesting an item?',
      a: 'Depending on the requested aid, proof may include a valid student ID (for learning devices), an endorsement letter from local leaders, or a photo/document describing the immediate situation.'
    },
    {
      q: 'Can I donate used items?',
      a: 'Yes, provided they are clean, fully functioning, and safe. We do not accept broken equipment, expired goods, or soiled garments.'
    },
    {
      q: 'How long does admin verification take?',
      a: 'Most submissions are verified within 4 to 24 hours. Once verified, you will receive notification and your post will be published on the platform.'
    }
  ],

  stats: {
    approvedDonations: '24+',
    itemsClaimed: '58+',
    communityUsers: '100+',
    volunteers: '58',
    sponsors: '12'
  }
};
