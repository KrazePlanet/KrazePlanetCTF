require 'erb'
require 'base64'
require 'json'
require 'ostruct'

template_b64 = ARGV[0] || ''
template_str = Base64.decode64(template_b64)

# Realistic Headless CMS & Landing Page Context
class CMSContext
  attr_accessor :brand, :campaign, :metrics, :author, :site

  def initialize
    @brand = OpenStruct.new(
      name: "CloudScale Engine",
      slug: "cloudscale-io",
      tagline: "Ultra-low latency serverless edge compute",
      support_email: "hello@cloudscale.io",
      founded_year: "2024"
    )
    @campaign = OpenStruct.new(
      code: "LAUNCH2026",
      discount: "30% OFF Annual Tier",
      expiry: "September 30, 2026",
      cta_text: "Claim Your Cloud Credits",
      tier: "Enterprise Scale"
    )
    @metrics = OpenStruct.new(
      active_deployments: "128,450+",
      global_regions: "42 Edge PoPs",
      customers_count: "14,200",
      uptime: "99.999%"
    )
    @author = OpenStruct.new(
      name: "Marcus Vance",
      role: "Head of Growth & Developer Marketing",
      email: "marcus.vance@cloudscale.io"
    )
    @site = OpenStruct.new(
      theme: "Cyber Slate Dark",
      version: "v3.6.0-cms",
      cdn_endpoint: "https://assets.cloudscale.internal"
    )
  end

  def get_binding
    binding
  end
end

begin
  context = CMSContext.new
  renderer = ERB.new(template_str, trim_mode: '-')
  output = renderer.result(context.get_binding)
  print output
rescue => e
  print "ERB Evaluation Error: #{e.message}"
end
