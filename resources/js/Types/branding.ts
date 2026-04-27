export interface CreatorProfileType {
    id: number;
    userId: number;
    displayName: string;
    bio: string | null;
    avatarUrl: string | null;
    expertise: string | null;
    socialLinks: Record<string, string> | null;
    featuredCourseIds: number[] | null;
}

export interface LandingPageSectionType {
    id: number;
    sectionType: 'hero' | 'about' | 'featured_courses' | 'testimonials' | 'cta';
    title: string | null;
    subtitle: string | null;
    content: string | null;
    imageUrl: string | null;
    ctaText: string | null;
    ctaUrl: string | null;
    sortOrder: number;
    isVisible: boolean;
    metadata: Record<string, unknown> | null;
}

export interface PlatformBrandingType {
    id: number;
    siteName: string;
    tagline: string | null;
    logoUrl: string | null;
    faviconUrl: string | null;
    primaryColor: string;
    secondaryColor: string;
    footerText: string | null;
}

export interface LandingPageDataType {
    branding: PlatformBrandingType;
    sections: LandingPageSectionType[];
    creatorProfile: CreatorProfileType | null;
    featuredCourses: FeaturedCourseType[];
}

export interface FeaturedCourseType {
    id: number;
    title: string;
    description: string;
    category: string;
    status: string;
    publishedAt: string | null;
}
