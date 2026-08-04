import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import DashboardLayout from "../../components/DashboardLayout";
import ticketService from "../../services/ticketService";
import { readCollection } from "../../api/axios.js";

const priorityOptions = ["Low", "Medium", "High", "Critical"];
const fallbackCategories = [
  { id: 1, categoryName: "Hardware" },
  { id: 2, categoryName: "Software" },
  { id: 3, categoryName: "Network" },
  { id: 4, categoryName: "Email" },
];

function CreateTicket() {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [categoryId, setCategoryId] = useState("");
  const [priority, setPriority] = useState("Medium");
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [fetchingCategories, setFetchingCategories] = useState(true);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const navigate = useNavigate();

  useEffect(() => {
    async function loadCategories() {
      try {
        const response = await ticketService.getCategories();
        setCategories(readCollection(response, "categories").length > 0 ? readCollection(response, "categories") : fallbackCategories);
        if (readCollection(response, "categories").length === 0) {
          setCategories(fallbackCategories);
        }
      } catch (err) {
        setCategories(fallbackCategories);
      } finally {
        setFetchingCategories(false);
      }
    }

    loadCategories();
  }, []);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError("");
    setSuccess("");
    setLoading(true);

    if (!title || !description || !categoryId) {
      setError("Please fill in all required fields.");
      setLoading(false);
      return;
    }

    try {
      await ticketService.createTicket({
        title,
        description,
        categoryId,
        priority,
      });

      setSuccess("Ticket created successfully.");
      navigate("/employee-dashboard/tickets");
    } catch (err) {
      setError(err?.response?.data?.message || "Unable to create ticket. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <DashboardLayout role="Employee" title="Create Ticket" subtitle="Submit a new support request and track it from your dashboard.">
      <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="mb-6">
          <h2 className="text-xl font-semibold text-slate-900">New Ticket</h2>
          <p className="mt-2 text-sm text-slate-500">Complete the form below to submit a support ticket.</p>
        </div>

        <form className="space-y-5" onSubmit={handleSubmit}>
          <div>
            <label htmlFor="title" className="mb-2 block text-sm font-medium text-slate-700">
              Title
            </label>
            <input
              id="title"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="Enter ticket title"
              className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            />
          </div>

          <div>
            <label htmlFor="description" className="mb-2 block text-sm font-medium text-slate-700">
              Description
            </label>
            <textarea
              id="description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              rows={5}
              placeholder="Describe the issue in detail"
              className="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            />
          </div>

          <div className="grid gap-5 lg:grid-cols-2">
            <div>
              <label htmlFor="category" className="mb-2 block text-sm font-medium text-slate-700">
                Category
              </label>
              <select
                id="category"
                value={categoryId}
                onChange={(e) => setCategoryId(e.target.value)}
                className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
              >
                <option value="">Select a category</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.categoryName}
                  </option>
                ))}
              </select>
              {fetchingCategories && <p className="mt-2 text-sm text-slate-500">Loading categories…</p>}
            </div>

            <div>
              <label htmlFor="priority" className="mb-2 block text-sm font-medium text-slate-700">
                Priority
              </label>
              <select
                id="priority"
                value={priority}
                onChange={(e) => setPriority(e.target.value)}
                className="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
              >
                {priorityOptions.map((option) => (
                  <option key={option} value={option}>
                    {option}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {error && <div className="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{error}</div>}
          {success && <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{success}</div>}

          <button
            type="submit"
            disabled={loading}
            className="inline-flex items-center justify-center rounded-3xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
          >
            {loading ? "Creating ticket..." : "Create Ticket"}
          </button>
        </form>
      </div>
    </DashboardLayout>
  );
}

export default CreateTicket;
