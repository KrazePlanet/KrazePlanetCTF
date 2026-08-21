import freemarker.template.*;
import java.io.*;
import java.util.*;

public class FreeMarkerEvaluator {
    public static void main(String[] args) {
        if (args.length == 0) {
            System.out.print("");
            return;
        }
        
        String templateStr = args[0];
        if ("--base64".equals(args[0]) && args.length > 1) {
            byte[] decoded = Base64.getDecoder().decode(args[1]);
            templateStr = new String(decoded, java.nio.charset.StandardCharsets.UTF_8);
        }

        try {
            Configuration cfg = new Configuration(Configuration.VERSION_2_3_32);
            cfg.setDefaultEncoding("UTF-8");
            cfg.setTemplateExceptionHandler(TemplateExceptionHandler.RETHROW_HANDLER);
            cfg.setLogTemplateExceptions(false);
            cfg.setWrapUncheckedExceptions(true);
            cfg.setNumberFormat("computer");
            
            // Rich enterprise SaaS Data Model
            Map<String, Object> data = new HashMap<>();
            
            Map<String, Object> user = new HashMap<>();
            user.put("name", "Alex Morgan");
            user.put("firstName", "Alex");
            user.put("lastName", "Morgan");
            user.put("email", "alex.morgan@apexcorp.internal");
            user.put("role", "Senior Marketing Director");
            user.put("loyaltyTier", "Platinum Executive");
            user.put("points", 14500);
            data.put("user", user);

            Map<String, Object> company = new HashMap<>();
            company.put("name", "PulseMail Enterprise Inc.");
            company.put("supportEmail", "support@pulsemail.io");
            company.put("website", "https://pulsemail.io");
            company.put("address", "742 Evergreen Terrace, San Francisco, CA 94107");
            data.put("company", company);

            Map<String, Object> order = new HashMap<>();
            order.put("id", "ORD-2026-98421");
            order.put("date", "August 17, 2026");
            order.put("total", "$349.50");
            order.put("status", "Confirmed");
            order.put("itemsCount", 3);
            data.put("order", order);

            data.put("campaignName", "Q3 Customer Appreciation & Product Keynote");
            data.put("currentYear", "2026");
            data.put("discountCode", "SUMMER2026-VIP");
            
            // Legacy flat variables for backward compatibility
            data.put("name", "Alex Morgan");
            data.put("email", "alex.morgan@apexcorp.internal");
            
            Template t = new Template("marketing_template", new StringReader(templateStr), cfg);
            StringWriter out = new StringWriter();
            t.process(data, out);
            System.out.print(out.toString());
        } catch (Exception e) {
            System.out.print("Template Compilation Error: " + e.getMessage());
        }
    }
}
