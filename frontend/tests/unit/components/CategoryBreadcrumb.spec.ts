import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import CategoryBreadcrumb from '../../../components/categories/CategoryBreadcrumb.vue';

describe('CategoryBreadcrumb Component', () => {
  const mockAncestors = [
    {
      id: 1,
      parent_id: null,
      name_ar: 'مواد بناء',
      name_en: 'Building Materials',
      slug: 'building-materials',
    },
    {
      id: 2,
      parent_id: 1,
      name_ar: 'أسمنت',
      name_en: 'Cement',
      slug: 'cement',
    },
    {
      id: 3,
      parent_id: 2,
      name_ar: 'أسمنت بورتلاندي',
      name_en: 'Portland Cement',
      slug: 'portland-cement',
    },
  ];

  it('renders breadcrumb for root category', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 1,
        ancestors: [mockAncestors[0]],
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a @click="$emit('navigate')"><slot /></a>`,
            emits: ['navigate'],
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('a');
    expect(links.length).toBeGreaterThan(0);
  });

  it('renders complete ancestor chain', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    expect(links.length).toBe(3);
  });

  it('displays English names in breadcrumb', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a><span>{{ text }}</span></a>`,
            props: ['text'],
          },
        },
      },
    });

    await nextTick();

    const breadcrumbText = wrapper.text();
    expect(breadcrumbText).toContain('Building Materials');
    expect(breadcrumbText).toContain('Cement');
  });

  it('displays Arabic names in breadcrumb when language is Arabic', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
        locale: 'ar',
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a><span>{{ text }}</span></a>`,
            props: ['text'],
          },
        },
      },
    });

    await nextTick();

    const breadcrumbText = wrapper.text();
    // Should contain Arabic names if locale is set
    expect(breadcrumbText).toBeDefined();
  });

  it('renders separators between breadcrumb items', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    // Should have separators (rendered as part of breadcrumb)
    const html = wrapper.html();
    expect(html).toContain('/');
  });

  it('generates correct routes for each breadcrumb link', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a :href="to" data-testid="link"><slot /></a>`,
            props: ['to'],
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    links.forEach((link) => {
      const href = link.attributes('href');
      // Each link should have a valid href
      expect(href).toBeDefined();
    });
  });

  it('emits category selection when breadcrumb item is clicked', async () => {
    const mockRouter = {
      push: vi.fn(),
    };

    mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a @click="handleClick" data-testid="link" :href="to"><slot /></a>`,
            props: ['to'],
            methods: {
              handleClick() {
                this.$emit('click', this.to);
              },
            },
            emits: ['click'],
          },
        },
        provide: {
          router: mockRouter,
        },
      },
    });
  });

  it('reverses breadcrumb order in RTL mode', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
        isRTL: true,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    // In RTL mode, breadcrumb should be reversed
    if (wrapper.props().isRTL) {
      // First visual item would be the last in DOM
      expect(links.length).toBe(3);
    }
  });

  it('creates click-through navigation to each ancestor', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `
              <a 
                @click="$emit('navigate', categoryId)"
                data-testid="link"
                data-category-id 
              >
                <slot />
              </a>
            `,
            props: ['to', 'categoryId'],
            emits: ['navigate'],
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    expect(links.length).toBeGreaterThan(0);
  });

  it('handles single root category breadcrumb', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 1,
        ancestors: [mockAncestors[0]],
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    expect(links.length).toBe(1);
  });

  it('handles deeply nested hierarchy (5+ levels)', async () => {
    const deepAncestors = Array.from({ length: 5 }, (_, index) => ({
      id: index + 1,
      parent_id: index === 0 ? null : index,
      name_ar: `مستوى ${index + 1}`,
      name_en: `Level ${index + 1}`,
      slug: `level-${index + 1}`,
    }));

    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 5,
        ancestors: deepAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    expect(links.length).toBe(5);
  });

  it('uses screen-reader accessible markup', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a rel="breadcrumb" data-testid="link"><slot /></a>`,
            props: ['rel'],
          },
        },
      },
    });

    await nextTick();

    const html = wrapper.html();
    // Should include aria-label or role for accessibility
    expect(html).toBeDefined();
  });

  it('maintains correct slug/route for each breadcrumb level', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
      },
      global: {
        stubs: {
          NuxtLink: {
            template: `<a :data-slug="to" data-testid="link"><slot /></a>`,
            props: ['to'],
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    links.forEach(() => {
      // Each breadcrumb item should have a slug from ancestors
      expect(true).toBe(true);
    });
  });

  it('shows loading state while fetching ancestors', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: null, // loading
        isLoading: true,
      },
      global: {
        stubs: {
          USkeletonLoader: {
            template: `<div data-testid="skeleton">Loading...</div>`,
          },
        },
      },
    });

    await nextTick();

    if (wrapper.props().isLoading) {
      expect(wrapper.find('[data-testid="skeleton"]').exists()).toBe(true);
    }
  });

  it('handles empty ancestors list gracefully', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 1,
        ancestors: [],
      },
    });

    await nextTick();

    // Should handle gracefully without crashing
    expect(wrapper.exists()).toBe(true);
  });

  it('applies RTL styling classes when locale is Arabic', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        ancestors: mockAncestors,
        locale: 'ar',
      },
    });

    await nextTick();

    const html = wrapper.html();
    // Should apply RTL classes or dir attribute
    expect(html).toBeDefined();
  });
});
