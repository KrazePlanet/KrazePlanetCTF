require 'erb'
require 'base64'
require 'json'
require 'ostruct'

template_b64 = ARGV[0] || ''
template_str = Base64.decode64(template_b64)

# Realistic SaaS Data Model / Binding Context
class TemplateContext
  attr_accessor :user, :account, :invoice, :server_time, :app_name

  def initialize
    @user = OpenStruct.new(
      name: "Marcus Vance",
      first_name: "Marcus",
      last_name: "Vance",
      email: "marcus.vance@rubycorp.internal",
      role: "Lead DevOps Architect",
      tier: "Enterprise Tier",
      avatar: "https://images.unsplash.com/photo-1534528741775-53994a69daeb"
    )
    @account = OpenStruct.new(
      id: "ACC-892301",
      plan: "RubyCloud Pro Dedicated",
      status: "Active",
      billing_cycle: "Monthly",
      created_at: "2026-01-15"
    )
    @invoice = OpenStruct.new(
      number: "INV-2026-0817",
      amount: "$1,250.00",
      due_date: "August 30, 2026",
      status: "Pending Payment",
      items_count: 5
    )
    @app_name = "CloudMatrix — Ruby Infrastructure Cloud"
    @server_time = Time.now.strftime("%Y-%m-%d %H:%M:%S UTC")
  end

  def get_binding
    binding
  end
end

begin
  context = TemplateContext.new
  renderer = ERB.new(template_str, trim_mode: '-')
  output = renderer.result(context.get_binding)
  print output
rescue => e
  print "ERB Evaluation Error: #{e.message}"
end
