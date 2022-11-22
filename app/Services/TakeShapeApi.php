<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class TakeShapeApi
{
    protected $key;
    protected $endpoint;
    protected $timeout = 5;

    public function __construct()
    {
        $this->key = env('TAKESHAPE_API_KEY');
        $this->endpoint = env('TAKESHAPE_ENDPOINT');
    }

    /**
     * Get the footer layout.
     */
    public function getFooter()
    {
        return $this->post("{
            getfooter {
              _id
              navigation {
                column {
                  headline
                  repeater {
                    title
                    url
                  }
                }
              }
              newsletter {
                headline
                statement
              }
            }
          }
        ");  
    }

    /**
     * Get the header layout.
     */
    public function getHeader()
    {
        return $this->post("{
          getHeader {
            Logo {
              _id
              caption
              credit
              description
              filename
              mimeType
              path
              sourceUrl
              title
              uploadStatus
            }
            Navigation {
              Dropdown
              Title
              URL
              featuredImage {
                _id
                caption
                credit
                description
                filename
                mimeType
                path
                sourceUrl
                title
                uploadStatus
              }
              featuredText
              featuredURL
              leftColumnNavItems {
                Title
                url
              }
              rightColumnNavItems {
                Title
                url
              }
            }
            _id
          }
        }        
        ");  
    }

    /**
     * Get data for the home page layout.
     */
    public function getHomePage()
    {
      return $this->post("{
        getHomePage {
          _id
          sections {
            ... on FrequentlyAskedQuestions {
              faQs {
                answer
                question
              }
              headline
            }
            ... on SectionCtaIntroSimple {
              heading
              linkText
              statement
              url
            }
            ... on SectionCtaSimpleCenter {
              SectionCtaSimpleCenter_button: button {
                openNewWindow
                text
                url
              }
              headline
              statement
            }
            ... on SectionCtaSplitWithImage {
              SectionCtaSplitWithImage_button: button {
                openNewWindow
                text
                url
              }
              headline
              preheadline
              statement
            }
            ... on SectionFeaturedFilterCategories {
              filters {
                filter {
                  headline
                  url
                }
              }
            }
            ... on SectionFeaturesAlternatingWithOptionalTestimonial {
              SectionFeaturesAlternatingWithOptionalTestimonial_button: button {
                openNewWindow
                text
                url
              }
              icon {
                path
              }
              headline
              nameAndTitle
              statement
              testimonial
              avatar {
                path
              }
              featuredImage{
                path
              }
            }
            ... on SectionFeaturesCenteredGrid {
              SectionFeaturesCenteredGrid_features: features {
                headline
                statement
              }
              headline
              preheadline
              statement
            }
            ... on SectionFeaturesOffsetGrid {
              SectionFeaturesOffsetGrid_features: features {
                headline
                statement
              }
              headline
            }
            ... on SectionHeader {
              headline
              preheadline
              statement
            }
            ... on SectionHero {
              buttons {
                button {
                  openNewWindow
                  text
                  url
                }
              }
              headline
              statement
              desktopFeaturedImage {
                path 
              }
              SectionHero_type: type
            }
            ... on SectionPopularSearches {
              searchTerms {
                searchTerm
                url
              }
            }
            ... on SectionProductCarouselWithTags {
              SectionProductCarouselWithTags_cards: cards {
                card {
                  location
                  price
                  productName
                  sku
                  tags {
                    tag {
                      tag
                      tagUrl
                    }
                  }
                  url
                  vendor
                  featuredImage{
                    path
                  }
                }
              }
              headline
            }
            ... on SectionProductOrCategoryCarousel {
              SectionProductOrCategoryCarousel_cards: cards {
                card {
                  headline
                  url
                  featuredImage{
                    path
                  }
                }
              }
              headline
            }
            ... on SectionStatsSimpleInCard {
              headline
              statement
              SectionStatsSimpleInCard_stats: stats {
                stat {
                  headline
                  statement
                }
              }
            }
         
            ... on SectionTestimonialWithLargeAvatar {
              name
              testimonial
              title
              avatar{
                path
              }
            }
            ... on Testimonial {
              name
              testimonial
              title
              Testimonial_type: type
            }
          }
          slug
        }
      }      
      ");
    }

    public function getBrand($handle) 
    {
      return $this->post("{
        getMerchantBrandPagesList {
          items {
            _id
            sections {
              __typename
              ... on FrequentlyAskedQuestions {
                faQs {
                  answer
                  question
                }
                headline
              }
              ... on SectionCtaIntroSimple {
                heading
                linkText
                statement
                url
              }
              ... on SectionCtaSimpleCenter {
                SectionCtaSimpleCenter_button: button {
                  openNewWindow
                  text
                  url
                }
                headline
                statement
              }
              ... on SectionCtaSplitWithImage {
                SectionCtaSplitWithImage_button: button {
                  openNewWindow
                  text
                  url
                }
                headline
                preheadline
                statement
              }
              ... on SectionFeaturedFilterCategories {
                filters {
                  filter {
                    headline
                    url
                  }
                }
              }
              ... on SectionFeaturesAlternatingWithOptionalTestimonial {
                SectionFeaturesAlternatingWithOptionalTestimonial_button: button {
                  openNewWindow
                  text
                  url
                }
                headline
                nameAndTitle
                statement
                testimonial
              }
              ... on SectionFeaturesCenteredGrid {
                SectionFeaturesCenteredGrid_features: features {
                  headline
                  statement
                }
                headline
                preheadline
                statement
              }
              ... on SectionFeaturesOffsetGrid {
                SectionFeaturesOffsetGrid_features: features {
                  headline
                  statement
                }
                headline
              }
              ... on SectionGeneralContent {
                content
                headline
              }
              ... on SectionHeader {
                headline
                preheadline
                statement
              }
              ... on SectionHero {
                buttons {
                  button {
                    openNewWindow
                    text
                    url
                  }
                }
                headline
                statement
                SectionHero_type: type
              }
              ... on SectionMerchantHero {
                desktopImage {
                  path
                }
                mobileImage {
                  path
                }
                logo {
                  path
                }
                cityAndState
                followHeadline
                followUrl
                fulfillmentTimeline
                headline
                missionHeadline
                missionStatement
                shippingCityAndState
                socialLinks {
                  link
                }
                tags {
                  tag
                }
              }
              ... on SectionPopularSearches {
                searchTerms {
                  searchTerm
                  url
                }
              }
              ... on SectionProductCarouselWithTags {
                SectionProductCarouselWithTags_cards: cards {
                  card {
                    location
                    price
                    productName
                    sku
                    tags {
                      tag {
                        tag
                        tagUrl
                      }
                    }
                    url
                    vendor
                  }
                }
                headline
              }
              ... on SectionGalleryCarousel {
                images {
                  image{
                    path
                  }
                }
              }
              ... on SectionProductOrCategoryCarousel {
                SectionProductOrCategoryCarousel_cards: cards {
                  card {
                    headline
                    url
                  }
                }
                headline
              }
              ... on SectionSplitWithVideo {
                headline
                statement
                videoUrl
              }
              ... on SectionStatsSimpleInCard {
                headline
                statement
                SectionStatsSimpleInCard_stats: stats {
                  stat {
                    headline
                    statement
                  }
                }
              }
              ... on SectionStatsSplitWithImage {
                headline
                statement
                SectionStatsSplitWithImage_stats: stats {
                  stat {
                    headline
                    statement
                  }
                }
              }
              ... on SectionTestimonialWithLargeAvatar {
                name
                testimonial
                title
                avatar {
                  path 
                }
              }
              ... on Testimonial {
                name
                testimonial
                title
                Testimonial_type: type
              }
            }
            slug
          }
          total
        }
      }
      ");
    }

    /*
     * Perform GET operation.
     */
    public function post($query)
    {
        try 
        {
            $client = new Client([
                'timeout' => $this->timeout,
            ]);

            $response = $client->post($this->endpoint, [
                'headers' => [
                    'Authorization' => "Bearer $this->key"
                ],
                'json' => [
                    'query' => $query
                  ]
            ]);
        } 
        catch(\Exception $e) { echo $e->getMessage(); }
    
        return json_decode((string)$response->getBody());
    }
}