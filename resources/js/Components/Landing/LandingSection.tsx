import { Link } from '@inertiajs/react';
import type {
    LandingPageSectionType,
    PlatformBrandingType,
    CreatorProfileType,
    FeaturedCourseType,
} from '@/Types/branding';
import FeaturedCourses from '@/Components/Landing/FeaturedCourses';

interface LandingSectionProps {
    section: LandingPageSectionType;
    branding: PlatformBrandingType;
    creatorProfile: CreatorProfileType | null;
    featuredCourses: FeaturedCourseType[];
}

const STATIC_TESTIMONIALS = [
    {
        quote: 'We replaced three tools with this platform. Enrollment and completion data finally live in one place.',
        name: 'Amelia Chen',
        role: 'Head of L&D, Northwind',
        initials: 'AC',
        color: 'bg-violet-200 text-violet-900',
    },
    {
        quote: 'Certificates were a pain before. Now learners get something credible the moment they finish.',
        name: 'Marcus Webb',
        role: 'Lead Creator, Brightline Studio',
        initials: 'MW',
        color: 'bg-blue-200 text-blue-900',
    },
    {
        quote: 'The catalog and subscription flow just works. Our team was live in a weekend.',
        name: 'Sofia Alvarez',
        role: 'COO, Harbor Labs',
        initials: 'SA',
        color: 'bg-emerald-200 text-emerald-900',
    },
];

interface TestimonialData {
    quote: string;
    name: string;
    role: string;
    initials: string;
    color: string;
}

function HeroSection({ section, branding }: { section: LandingPageSectionType; branding: PlatformBrandingType }) {
    return (
        <section className="relative overflow-hidden bg-white">
            <div
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(124,58,237,0.12),transparent),radial-gradient(ellipse_60%_40%_at_100%_0%,rgba(59,130,246,0.08),transparent)]"
                aria-hidden
            />
            <div className="relative mx-auto max-w-6xl px-4 pb-16 pt-14 text-center sm:px-6 sm:pt-20 lg:px-8 lg:pt-24">
                {section.title && (
                    <h1 className="mt-6 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl lg:leading-[1.1]">
                        {section.title}
                    </h1>
                )}
                {section.subtitle && (
                    <p className="mx-auto mt-6 max-w-2xl text-base text-gray-600 sm:text-lg">
                        {section.subtitle}
                    </p>
                )}
                {section.imageUrl && (
                    <div className="mx-auto mt-10 max-w-4xl">
                        <img
                            src={section.imageUrl}
                            alt={section.title ?? 'Hero image'}
                            className="rounded-2xl shadow-lg"
                        />
                    </div>
                )}
                {section.ctaText && (
                    <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                        <Link
                            href={section.ctaUrl ?? '/register'}
                            className="inline-flex w-full min-w-[160px] items-center justify-center rounded-full px-8 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:opacity-90 sm:w-auto"
                            style={{ backgroundColor: branding.primaryColor }}
                        >
                            {section.ctaText}
                        </Link>
                    </div>
                )}
            </div>
        </section>
    );
}

function AboutSection({
    section,
    creatorProfile,
}: {
    section: LandingPageSectionType;
    creatorProfile: CreatorProfileType | null;
}) {
    return (
        <section className="py-20" id="about">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div className="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        {section.title && (
                            <h2 className="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                                {section.title}
                            </h2>
                        )}
                        {section.subtitle && (
                            <p className="mt-4 text-lg text-gray-600">{section.subtitle}</p>
                        )}
                        {section.content && (
                            <p className="mt-4 text-sm leading-relaxed text-gray-600">{section.content}</p>
                        )}
                        {creatorProfile && (
                            <div className="mt-8 flex items-center gap-4">
                                {creatorProfile.avatarUrl ? (
                                    <img
                                        src={creatorProfile.avatarUrl}
                                        alt={creatorProfile.displayName}
                                        className="h-12 w-12 rounded-full object-cover"
                                    />
                                ) : (
                                    <span className="flex h-12 w-12 items-center justify-center rounded-full bg-violet-200 text-sm font-bold text-violet-900">
                                        {creatorProfile.displayName.charAt(0).toUpperCase()}
                                    </span>
                                )}
                                <div>
                                    <p className="text-sm font-semibold text-gray-900">
                                        {creatorProfile.displayName}
                                    </p>
                                    {creatorProfile.expertise && (
                                        <p className="text-xs text-gray-500">{creatorProfile.expertise}</p>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                    {section.imageUrl && (
                        <div>
                            <img
                                src={section.imageUrl}
                                alt={section.title ?? 'About image'}
                                className="rounded-2xl shadow-lg"
                            />
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}

function FeaturedCoursesSection({
    section,
    featuredCourses,
    creatorProfile,
    branding,
}: {
    section: LandingPageSectionType;
    featuredCourses: FeaturedCourseType[];
    creatorProfile: CreatorProfileType | null;
    branding: PlatformBrandingType;
}) {
    return (
        <section className="border-t border-gray-100 bg-gray-50/50" id="courses">
            <div className="mx-auto max-w-6xl px-4 pt-20 sm:px-6 lg:px-8">
                <p className="text-center text-xs font-semibold uppercase tracking-wider text-violet-600">
                    Our courses
                </p>
                {section.title && (
                    <h2 className="mt-2 text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        {section.title}
                    </h2>
                )}
                {section.subtitle && (
                    <p className="mx-auto mt-3 max-w-2xl text-center text-gray-600">
                        {section.subtitle}
                    </p>
                )}
            </div>
            <FeaturedCourses
                courses={featuredCourses}
                creatorName={creatorProfile?.displayName ?? null}
                branding={branding}
            />
        </section>
    );
}

function TestimonialsSection({ section }: { section: LandingPageSectionType }) {
    let testimonials: TestimonialData[] = STATIC_TESTIMONIALS;

    if (
        section.metadata &&
        Array.isArray(section.metadata.testimonials) &&
        section.metadata.testimonials.length > 0
    ) {
        const COLORS = [
            'bg-violet-200 text-violet-900',
            'bg-blue-200 text-blue-900',
            'bg-emerald-200 text-emerald-900',
        ];
        testimonials = (section.metadata.testimonials as Array<{ quote?: string; name?: string; role?: string }>).map(
            (t, i) => ({
                quote: t.quote ?? '',
                name: t.name ?? 'Anonymous',
                role: t.role ?? '',
                initials: (t.name ?? 'A')
                    .split(' ')
                    .map((w: string) => w[0])
                    .join('')
                    .toUpperCase()
                    .slice(0, 2),
                color: COLORS[i % COLORS.length],
            }),
        );
    }

    return (
        <section className="border-t border-gray-100 bg-gray-50/50 py-20" id="testimonials">
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <p className="text-center text-xs font-semibold uppercase tracking-wider text-violet-600">
                    Testimonials
                </p>
                {section.title && (
                    <h2 className="mt-2 text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        {section.title}
                    </h2>
                )}
                <div className="mt-12 grid gap-6 md:grid-cols-3">
                    {testimonials.map((t) => (
                        <figure
                            key={t.name}
                            className="flex flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                        >
                            <div className="flex gap-0.5 text-amber-400" aria-label="5 out of 5 stars">
                                {'★★★★★'.split('').map((s, i) => (
                                    <span key={i}>{s}</span>
                                ))}
                            </div>
                            <blockquote className="mt-4 flex-1 text-sm leading-relaxed text-gray-700">
                                &ldquo;{t.quote}&rdquo;
                            </blockquote>
                            <figcaption className="mt-6 flex items-center gap-3">
                                <span
                                    className={`flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold ${t.color}`}
                                >
                                    {t.initials}
                                </span>
                                <div>
                                    <div className="text-sm font-semibold text-gray-900">{t.name}</div>
                                    <div className="text-xs text-gray-500">{t.role}</div>
                                </div>
                            </figcaption>
                        </figure>
                    ))}
                </div>
            </div>
        </section>
    );
}

function CtaSection({ section, branding }: { section: LandingPageSectionType; branding: PlatformBrandingType }) {
    return (
        <section className="bg-gray-950 py-20 text-white">
            <div className="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                {section.title && (
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">{section.title}</h2>
                )}
                {section.subtitle && <p className="mt-4 text-gray-400">{section.subtitle}</p>}
                {section.ctaText && (
                    <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                        <Link
                            href={section.ctaUrl ?? '/register'}
                            className="inline-flex w-full min-w-[180px] items-center justify-center rounded-full px-8 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:opacity-90 sm:w-auto"
                            style={{ backgroundColor: branding.primaryColor }}
                        >
                            {section.ctaText}
                        </Link>
                    </div>
                )}
            </div>
        </section>
    );
}

export default function LandingSection({
    section,
    branding,
    creatorProfile,
    featuredCourses,
}: LandingSectionProps) {
    switch (section.sectionType) {
        case 'hero':
            return <HeroSection section={section} branding={branding} />;
        case 'about':
            return <AboutSection section={section} creatorProfile={creatorProfile} />;
        case 'featured_courses':
            return (
                <FeaturedCoursesSection
                    section={section}
                    featuredCourses={featuredCourses}
                    creatorProfile={creatorProfile}
                    branding={branding}
                />
            );
        case 'testimonials':
            return <TestimonialsSection section={section} />;
        case 'cta':
            return <CtaSection section={section} branding={branding} />;
        default:
            return null;
    }
}
