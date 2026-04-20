import LandingLayout from '@/Components/Layout/LandingLayout';
import { Link } from '@inertiajs/react';
import type { CatalogCourseType } from '@/Types/catalog';
import type { MembershipPlanType } from '@/Types/subscription';

interface LandingProps {
    featuredCourses: CatalogCourseType[];
    plans: MembershipPlanType[];
}

const COURSE_ACCENTS = [
    'from-violet-500 to-purple-600',
    'from-blue-500 to-indigo-600',
    'from-emerald-500 to-teal-600',
    'from-orange-500 to-amber-600',
    'from-pink-500 to-rose-600',
    'from-cyan-500 to-blue-600',
] as const;

const DEMO_COURSES: CatalogCourseType[] = [
    {
        id: 0,
        title: 'UI/UX Design Fundamentals',
        descriptionSummary: 'Layout, typography, and usability patterns for interfaces people love.',
        instructorName: 'GrowthPedia Faculty',
        category: 'Design',
        publishedAt: '',
    },
    {
        id: 0,
        title: 'Digital Marketing Strategy',
        descriptionSummary: 'Campaign planning, analytics, and growth loops for modern teams.',
        instructorName: 'GrowthPedia Faculty',
        category: 'Marketing',
        publishedAt: '',
    },
    {
        id: 0,
        title: 'Data Literacy Essentials',
        descriptionSummary: 'Read charts, ask better questions, and make decisions with confidence.',
        instructorName: 'GrowthPedia Faculty',
        category: 'Data',
        publishedAt: '',
    },
    {
        id: 0,
        title: 'Product Thinking Workshop',
        descriptionSummary: 'Discovery, roadmaps, and outcomes for product builders.',
        instructorName: 'GrowthPedia Faculty',
        category: 'Product',
        publishedAt: '',
    },
    {
        id: 0,
        title: 'Leadership Communication',
        descriptionSummary: 'Stories, feedback, and clarity for managers and leads.',
        instructorName: 'GrowthPedia Faculty',
        category: 'Leadership',
        publishedAt: '',
    },
    {
        id: 0,
        title: 'Automation with No-Code',
        descriptionSummary: 'Ship internal tools faster without a dedicated engineering team.',
        instructorName: 'GrowthPedia Faculty',
        category: 'Operations',
        publishedAt: '',
    },
];

const FEATURES = [
    {
        title: 'Expert-led courses',
        body: 'Structured modules and lessons from instructors who care about outcomes — not just slides.',
        icon: '🎓',
        tint: 'bg-amber-100 text-amber-800',
    },
    {
        title: 'Flexible learning',
        body: 'Resume where you left off, on your schedule. Progress stays accurate when content updates.',
        icon: '⏱️',
        tint: 'bg-violet-100 text-violet-800',
    },
    {
        title: 'Certificates that verify',
        body: 'Earn PDF certificates with verification codes when you complete a course.',
        icon: '✓',
        tint: 'bg-blue-100 text-blue-800',
    },
    {
        title: 'Built-in discussions',
        body: 'Per-lesson threads so learners ask questions without leaving the flow.',
        icon: '💬',
        tint: 'bg-emerald-100 text-emerald-800',
    },
];

const TESTIMONIALS = [
    {
        quote:
            'We replaced three tools with GrowthPedia. Enrollment and completion data finally live in one place.',
        name: 'Amelia Chen',
        role: 'Head of L&D, Northwind',
        initials: 'AC',
        color: 'bg-violet-200 text-violet-900',
    },
    {
        quote: 'Certificates were a pain before. Now learners get something credible the moment they finish.',
        name: 'Marcus Webb',
        role: 'Lead Instructor, Brightline Studio',
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

const TRUST_LABELS = ['Northwind', 'Brightline', 'Harbor Labs', 'Stellar', 'Vertex'];

const STATIC_PRICING_TIERS: {
    name: string;
    price: string;
    period: string;
    blurb: string;
    features: string[];
    highlight: boolean;
}[] = [
    {
        name: 'Basic',
        price: 'Rp 0',
        period: '/month',
        blurb: 'Explore the catalog and sample lessons.',
        features: ['Browse public courses', 'Community read-only', 'Email support'],
        highlight: false,
    },
    {
        name: 'Pro',
        price: 'Rp 299.000',
        period: '/month',
        blurb: 'Full access for serious learners.',
        features: ['Unlimited enrolled courses', 'Certificates', 'Lesson discussions', 'Priority support'],
        highlight: true,
    },
    {
        name: 'Team',
        price: 'Rp 799.000',
        period: '/month',
        blurb: 'For squads learning together.',
        features: ['Everything in Pro', 'Admin analytics', 'CSV exports', 'Dedicated success manager'],
        highlight: false,
    },
];

function formatPlanPrice(plan: MembershipPlanType): string {
    return `Rp ${Number(plan.price).toLocaleString('id-ID')}`;
}

function planFeatures(plan: MembershipPlanType): string[] {
    if (plan.description && plan.description.trim() !== '') {
        const lines = plan.description
            .split(/\n|;|,/)
            .map((s) => s.trim())
            .filter(Boolean);
        if (lines.length >= 2) {
            return lines.slice(0, 5);
        }
    }
    return ['Active membership', 'Course access per plan', 'Progress tracking', 'Certificates when eligible'];
}

function buildDisplayCourses(server: CatalogCourseType[]): CatalogCourseType[] {
    const merged = [...server];
    let i = 0;
    while (merged.length < 6 && i < DEMO_COURSES.length) {
        const demo = DEMO_COURSES[i];
        if (!merged.some((c) => c.title === demo.title)) {
            merged.push(demo);
        }
        i += 1;
    }
    return merged.slice(0, 6);
}

export default function Landing({ featuredCourses, plans }: LandingProps) {
    const displayCourses = buildDisplayCourses(featuredCourses);

    const pricingFromDb =
        plans.length > 0
            ? plans.slice(0, 3).map((plan, index) => ({
                  kind: 'db' as const,
                  plan,
                  highlight: plans.length >= 3 ? index === 1 : index === plans.length - 1,
              }))
            : null;

    return (
        <LandingLayout>
            <section className="relative overflow-hidden bg-white">
                <div
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(124,58,237,0.12),transparent),radial-gradient(ellipse_60%_40%_at_100%_0%,rgba(59,130,246,0.08),transparent)]"
                    aria-hidden
                />
                <div className="relative mx-auto max-w-6xl px-4 pb-16 pt-14 text-center sm:px-6 sm:pt-20 lg:px-8 lg:pt-24">
                    <p className="inline-flex rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-violet-700">
                        The best way to learn new skills
                    </p>
                    <h1 className="mt-6 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl lg:leading-[1.1]">
                        Master the skills that{' '}
                        <span className="bg-gradient-to-r from-violet-600 to-blue-600 bg-clip-text text-transparent">
                            shape the future
                        </span>
                        .
                    </h1>
                    <p className="mx-auto mt-6 max-w-2xl text-base text-gray-600 sm:text-lg">
                        GrowthPedia brings courses, memberships, progress, and certificates into one calm experience —
                        for learners who want depth and teams who want clarity.
                    </p>
                    <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                        <Link
                            href="/register"
                            className="inline-flex w-full min-w-[160px] items-center justify-center rounded-full bg-gray-900 px-8 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:bg-gray-800 sm:w-auto"
                        >
                            Get started
                        </Link>
                        <Link
                            href="/catalog"
                            className="inline-flex w-full min-w-[160px] items-center justify-center rounded-full border border-gray-300 bg-white px-8 py-3.5 text-sm font-semibold text-gray-900 shadow-sm transition hover:border-gray-400 hover:bg-gray-50 sm:w-auto"
                        >
                            Browse courses
                        </Link>
                    </div>
                    <div className="mx-auto mt-14 max-w-4xl rounded-2xl border border-gray-100 bg-gray-50/80 px-4 py-6 backdrop-blur sm:px-8">
                        <dl className="grid grid-cols-2 gap-6 sm:grid-cols-4">
                            {[
                                ['100k+', 'Active learners'],
                                ['500+', 'Expert tutors'],
                                ['60+', 'Countries'],
                                ['4.8/5', 'Average rating'],
                            ].map(([stat, label]) => (
                                <div key={label} className="text-center">
                                    <dt className="text-2xl font-bold text-gray-900 sm:text-3xl">{stat}</dt>
                                    <dd className="mt-1 text-xs text-gray-500 sm:text-sm">{label}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </div>
            </section>

            <section className="border-t border-gray-100 bg-gray-50/50 py-20" id="courses">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <p className="text-center text-xs font-semibold uppercase tracking-wider text-violet-600">Our courses</p>
                    <h2 className="mt-2 text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Featured courses
                    </h2>
                    <p className="mx-auto mt-3 max-w-2xl text-center text-gray-600">
                        A snapshot of what you can start today — including live catalog picks when available.
                    </p>
                    <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {displayCourses.map((course, index) => {
                            const href = course.id > 0 ? `/catalog/${course.id}` : '/catalog';
                            const accent = COURSE_ACCENTS[index % COURSE_ACCENTS.length];
                            return (
                                <Link
                                    key={`${course.title}-${index}`}
                                    href={href}
                                    className="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:border-violet-200 hover:shadow-md"
                                >
                                    <div
                                        className={`relative flex h-36 items-center justify-center bg-gradient-to-br ${accent} text-4xl transition group-hover:opacity-95`}
                                    >
                                        <span className="drop-shadow-sm" aria-hidden>
                                            {['◆', '◇', '○', '△', '□', '◎'][index % 6]}
                                        </span>
                                    </div>
                                    <div className="flex flex-1 flex-col p-5">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-violet-600">
                                            {course.category}
                                        </p>
                                        <h3 className="mt-2 text-lg font-bold text-gray-900 group-hover:text-violet-700">
                                            {course.title}
                                        </h3>
                                        <p className="mt-2 line-clamp-2 flex-1 text-sm text-gray-600">
                                            {course.descriptionSummary}
                                        </p>
                                        <div className="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 text-xs text-gray-500">
                                            <span>Self-paced</span>
                                            <span className="font-medium text-violet-600">View course →</span>
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                    <div className="mt-12 text-center">
                        <Link
                            href="/catalog"
                            className="inline-flex rounded-full border border-gray-300 bg-white px-6 py-2.5 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-50"
                        >
                            View all courses
                        </Link>
                    </div>
                </div>
            </section>

            <section className="py-20" id="features">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <p className="text-center text-xs font-semibold uppercase tracking-wider text-violet-600">Why choose us</p>
                    <h2 className="mt-2 text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Everything you need to level up
                    </h2>
                    <p className="mx-auto mt-3 max-w-2xl text-center text-gray-600">
                        One platform for structured content, honest progress, and proof of completion.
                    </p>
                    <div className="mt-14 grid gap-6 sm:grid-cols-2">
                        {FEATURES.map((f) => (
                            <div
                                key={f.title}
                                className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:border-violet-100 hover:shadow-md sm:p-8"
                            >
                                <span
                                    className={`inline-flex h-12 w-12 items-center justify-center rounded-xl text-xl ${f.tint}`}
                                    aria-hidden
                                >
                                    {f.icon}
                                </span>
                                <h3 className="mt-4 text-lg font-semibold text-gray-900">{f.title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-gray-600">{f.body}</p>
                            </div>
                        ))}
                    </div>
                    <div className="mt-20 text-center">
                        <p className="text-xs font-semibold uppercase tracking-wider text-gray-400">Trusted by leading teams</p>
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-x-10 gap-y-6 opacity-70 grayscale">
                            {TRUST_LABELS.map((name) => (
                                <span key={name} className="text-lg font-bold text-gray-400">
                                    {name}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            <section className="border-t border-gray-100 bg-gray-50/50 py-20" id="testimonials">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <p className="text-center text-xs font-semibold uppercase tracking-wider text-violet-600">Testimonials</p>
                    <h2 className="mt-2 text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Loved by thousands of learners
                    </h2>
                    <div className="mt-12 grid gap-6 md:grid-cols-3">
                        {TESTIMONIALS.map((t) => (
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

            <section className="py-20" id="pricing">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <p className="text-center text-xs font-semibold uppercase tracking-wider text-violet-600">Pricing</p>
                    <h2 className="mt-2 text-center text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Simple, transparent pricing
                    </h2>
                    <p className="mx-auto mt-3 max-w-2xl text-center text-gray-600">
                        Pick a membership that fits. Change or cancel when your needs evolve.
                    </p>
                    <div className="mt-12 grid gap-6 lg:grid-cols-3">
                        {pricingFromDb
                            ? pricingFromDb.map(({ plan, highlight }) => (
                                  <div
                                      key={plan.id}
                                      className={`relative flex flex-col rounded-2xl border p-8 shadow-sm ${
                                          highlight
                                              ? 'border-gray-900 bg-gray-900 text-white ring-2 ring-violet-500'
                                              : 'border-gray-200 bg-white'
                                      }`}
                                  >
                                      {highlight && (
                                          <span className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-violet-600 px-3 py-0.5 text-xs font-semibold text-white">
                                              Most popular
                                          </span>
                                      )}
                                      <h3 className={`text-lg font-semibold ${highlight ? 'text-white' : 'text-gray-900'}`}>
                                          {plan.name}
                                      </h3>
                                      <p className={`mt-2 text-sm ${highlight ? 'text-gray-300' : 'text-gray-600'}`}>
                                          {plan.description ?? 'Membership access on GrowthPedia.'}
                                      </p>
                                      <div className="mt-6">
                                          <span className={`text-4xl font-bold ${highlight ? 'text-white' : 'text-gray-900'}`}>
                                              {formatPlanPrice(plan)}
                                          </span>
                                          <span className={`text-sm ${highlight ? 'text-gray-400' : 'text-gray-500'}`}>
                                              /{plan.billingFrequency === 'monthly' ? 'month' : 'year'}
                                          </span>
                                      </div>
                                      <ul className="mt-8 flex-1 space-y-3 text-sm">
                                          {planFeatures(plan).map((line) => (
                                              <li key={line} className="flex gap-2">
                                                  <span className={highlight ? 'text-violet-300' : 'text-violet-600'}>✓</span>
                                                  <span className={highlight ? 'text-gray-200' : 'text-gray-600'}>{line}</span>
                                              </li>
                                          ))}
                                      </ul>
                                      <Link
                                          href={`/checkout/${plan.id}`}
                                          className={`mt-8 block w-full rounded-full py-3 text-center text-sm font-semibold transition ${
                                              highlight
                                                  ? 'bg-violet-600 text-white hover:bg-violet-500'
                                                  : 'bg-gray-900 text-white hover:bg-gray-800'
                                          }`}
                                      >
                                          Get started
                                      </Link>
                                  </div>
                              ))
                            : STATIC_PRICING_TIERS.map((tier) => (
                                  <div
                                      key={tier.name}
                                      className={`relative flex flex-col rounded-2xl border p-8 shadow-sm ${
                                          tier.highlight
                                              ? 'border-gray-900 bg-gray-900 text-white ring-2 ring-violet-500'
                                              : 'border-gray-200 bg-white'
                                      }`}
                                  >
                                      {tier.highlight && (
                                          <span className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-violet-600 px-3 py-0.5 text-xs font-semibold text-white">
                                              Most popular
                                          </span>
                                      )}
                                      <h3 className={`text-lg font-semibold ${tier.highlight ? 'text-white' : 'text-gray-900'}`}>
                                          {tier.name}
                                      </h3>
                                      <p className={`mt-2 text-sm ${tier.highlight ? 'text-gray-300' : 'text-gray-600'}`}>
                                          {tier.blurb}
                                      </p>
                                      <div className="mt-6">
                                          <span className={`text-4xl font-bold ${tier.highlight ? 'text-white' : 'text-gray-900'}`}>
                                              {tier.price}
                                          </span>
                                          <span className={`text-sm ${tier.highlight ? 'text-gray-400' : 'text-gray-500'}`}>
                                              {tier.period}
                                          </span>
                                      </div>
                                      <ul className="mt-8 flex-1 space-y-3 text-sm">
                                          {tier.features.map((line) => (
                                              <li key={line} className="flex gap-2">
                                                  <span className={tier.highlight ? 'text-violet-300' : 'text-violet-600'}>✓</span>
                                                  <span className={tier.highlight ? 'text-gray-200' : 'text-gray-600'}>{line}</span>
                                              </li>
                                          ))}
                                      </ul>
                                      <Link
                                          href="/plans"
                                          className={`mt-8 block w-full rounded-full py-3 text-center text-sm font-semibold transition ${
                                              tier.highlight
                                                  ? 'bg-violet-600 text-white hover:bg-violet-500'
                                                  : 'bg-gray-900 text-white hover:bg-gray-800'
                                          }`}
                                      >
                                          Get started
                                      </Link>
                                  </div>
                              ))}
                    </div>
                    <p className="mt-8 text-center text-sm text-gray-500">
                        All plans include access to our community and support during business hours.
                    </p>
                </div>
            </section>

            <section className="bg-gray-950 py-20 text-white">
                <div className="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">Ready to start your learning journey?</h2>
                    <p className="mt-4 text-gray-400">
                        Join learners and teams who use GrowthPedia to ship skills — not just watch videos.
                    </p>
                    <div className="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                        <Link
                            href="/register"
                            className="inline-flex w-full min-w-[180px] items-center justify-center rounded-full bg-violet-600 px-8 py-3.5 text-sm font-semibold text-white shadow-lg transition hover:bg-violet-500 sm:w-auto"
                        >
                            Get started now
                        </Link>
                        <Link
                            href="/catalog"
                            className="inline-flex w-full min-w-[180px] items-center justify-center rounded-full border border-white/30 bg-transparent px-8 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto"
                        >
                            Browse catalog
                        </Link>
                    </div>
                </div>
            </section>
        </LandingLayout>
    );
}
